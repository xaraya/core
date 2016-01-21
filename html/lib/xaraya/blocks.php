<?php
/**
 * Display Blocks
 * *
 * @package core
 * @subpackage blocks
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Paul Rosania
 * @author Chris Powis
 */
interface ixarBlock
{
    const TYPE_STATE_ACTIVE = 1; 
    const TYPE_STATE_MISSING = 2;
    const TYPE_STATE_ERROR = 3;    
    const TYPE_STATE_MOD_UNAVAILABLE = 4; 

    const BLOCK_STATE_HIDDEN = 0;   // Hidden blocks still execute, they just don't render
    const BLOCK_STATE_INACTIVE = 1; // Inactive blocks don't execute, don't render
    const BLOCK_STATE_VISIBLE = 2;

    public static function render(Array $data=array());
    public static function renderBlock(Array $args=array());
    public static function renderGroup($groupname, $template=null);
    public static function hasMethod(iBlock $block, $method, $strict=false);
    public static function guiMethod(iBlock $block, $method);
    public static function checkAccess(iBlock $block, $action, $roleid=null);

}
class xarBlock extends Object implements ixarBlock
{
    
    private function __construct()
    {}
/**
 * Initialize blocks subsystem
 *
 * @author Paul Rosania
 * 
 * @param  array args
 * @return boolean
 */
    public static function init(&$args)
    {
        // Blocks Support Tables
        sys::import('modules.blocks.xartables');
        $tables = blocks_xartables();
        xarDB::importTables($tables);
        return true;    
    }

/**
 * Renders a block instance
 *
 * @author Paul Rosania
 * @author Marco Canini <marco@xaraya.com>
 * @author Chris Powis 
 * 
 * @param  array data block information parameters
 * @return string output the block to show
 * @throws  BAD_PARAM, DATABASE_ERROR, ID_NOT_EXIST, MODULE_FILE_NOT_EXIST
 * @todo   this function calls a module function, keep an eye on it.
 */
    public static function render(Array $blockinfo=array())
    {
        // Get a cache key for this block if it's suitable for block caching
        $cacheKey = xarCache::getBlockKey($blockinfo);

        // Check if the block is cached
        if (!empty($cacheKey) && xarBlockCache::isCached($cacheKey)) {
            // Return the cached block output
            return xarBlockCache::getCached($cacheKey);
        } 
        
        try {
            // get the block instance
            $block = self::getObject($blockinfo, 'display');

            // check if block expired already
            $now = time();
            if ($block->expire && $now > $block->expire) {
                if (!empty($cacheKey))
                    xarBlockCache::setCached($cacheKey, '');
                return '';
            }
            // checkAccess for display method
            if (!$block->checkAccess('display')) {
                if (!empty($cacheKey)) 
                    xarBlockCache::setCached($cacheKey, '');
                if (isset($block->display_access) && $block->display_access['failure']) {
                    // @TODO: render to an error/exception block?
                    return xarTpl::module('privileges','user','errors',
                        array('layout' => 'no_block_privileges'));
                }
                return '';
            }
            // don't render hidden blocks
            if ($block->state == xarBlock::BLOCK_STATE_HIDDEN) {
                // just execute the display method and return an empty string
                $block->display();
                if (!empty($cacheKey)) 
                    xarBlockCache::setCached($cacheKey, '');
                return '';
            }
            // render the block 
            $blockinfo['content'] = self::guiMethod($block, 'display');
            // no content, ok, nothing to display 
            if (empty($blockinfo['content'])) {
                if (!empty($cacheKey)) 
                    xarBlockCache::setCached($cacheKey, '');
                return '';
            }
            // render to box template if necessary 
            if ($block->type_category == 'group') {
                $boxOutput = $blockinfo['content'];
            } else {
                // title may have been over-ridden by the block setTitle() method 
                $blockinfo['title'] = $block->title;
                $blockinfo['_bl_block_id']       = $block->block_id;
                $blockinfo['_bl_block_name']     = $block->name;
                $blockinfo['_bl_block_type']     = $block->type;
                $blockinfo['_bl_block_type_id']  = $block->type_id;
                $blockinfo['_bl_block_group']    = $block->group;
                $blockinfo['_bl_block_group_id'] = $block->group_id;
                // @todo: deprecate use of these 
                $blockinfo['group'] = $block->group;
                $blockinfo['group_id'] = $block->group_id;
                $boxOutput = xarTpl::renderBlockBox($blockinfo, $block->box_template);
            }                      

            // Set the output of the block in cache
            if (!empty($cacheKey)) 
                xarBlockCache::setCached($cacheKey, $boxOutput);

            return $boxOutput;
            
        } catch (Exception $e) {
            if ((bool) xarModVars::get('blocks', 'noexceptions') || 
                !in_array(xarUser::getVar('id'),xarConfigVars::get(null,'Site.User.DebugAdmins'))) {
                if (!empty($cacheKey))
                    xarBlockCache::setCached($cacheKey, '');
                return '';
            } else {
                throw($e); 
            }
        }

    }

    public static function getObject(Array $blockinfo=array(), $interface=null, $method=null)
    {
        $invalid = array();
        if (empty($blockinfo['type']) || !is_string($blockinfo['type']))
            $invalid[] = 'type';
        if (!empty($blockinfo['module']) && !is_string($blockinfo['module']))
            $invalid[] = 'module';
        if (isset($interface) && !is_string($interface))
            $invalid[] = 'interface';
        if (isset($method) && !is_string($method))
            $invalid[] = 'method';
        if (!empty($invalid)) {
            $msg = 'Invalid #(1) for #(2) subsystem #(3) class method #(4)()';
            $vars = array(join(', ', $invalid), 'blocks', 'xarBlock', 'getObject');
            throw new BadParameterException($vars, $msg);        
        }
        
        if (empty($blockinfo['module'])) {
            $baseclass = ucfirst($blockinfo['type']).'Block';
            $basedp = "blocks";
            $basepath = sys::code().'blocks';   
        } else {
            $baseclass = ucfirst($blockinfo['module']).'_'.ucfirst($blockinfo['type']).'Block';
            $basedp = "modules.{$blockinfo['module']}.xarblocks";
            $basepath = sys::code()."modules/{$blockinfo['module']}/xarblocks";
        }

        $cls = array();
        $dps = array();
        $paths = array();
        if (!empty($interface)) {
            // blocks/type/type_interface.php | modules/module/xarblocks/type/type_interface.php
            $cls[] = $baseclass . ucfirst($interface);
            $paths[] = "{$basepath}/{$blockinfo['type']}/{$blockinfo['type']}_{$interface}.php";
            $dps[] = "{$basedp}.{$blockinfo['type']}.{$blockinfo['type']}_{$interface}";
            // blocks/type/interface.php | modules/module/xarblocks/type/interface.php
            $cls[] = $baseclass . ucfirst($interface);
            $paths[] = "{$basepath}/{$blockinfo['type']}/{$interface}.php";
            $dps[] = "{$basedp}.{$blockinfo['type']}.{$interface}";
            if (!empty($blockinfo['module'])) {
                // modules/module/xarblocks/type_interface.php
                $cls[] = $baseclass . ucfirst($interface);
                $paths[] = "{$basepath}/{$blockinfo['type']}_{$interface}.php";            
                $dps[] = "{$basedp}.{$blockinfo['type']}_{$interface}";
            }
            if ($interface != 'display' && $interface != 'admin') {
                // blocks/type/type_admin.php | modules/module/xarblocks/type/type_admin.php
                $cls[] = $baseclass . 'Admin';
                $paths[] = "{$basepath}/{$blockinfo['type']}/{$blockinfo['type']}_admin.php";
                $dps[] = "{$basedp}.{$blockinfo['type']}.{$blockinfo['type']}_admin";
                // blocks/type/admin.php | modules/module/xarblocks/type/admin.php
                $cls[] = $baseclass . 'Admin';
                $paths[] = "{$basepath}/{$blockinfo['type']}/admin.php";
                $dps[] = "{$basedp}.{$blockinfo['type']}.admin";
                if (!empty($blockinfo['module'])) {
                    // modules/module/xarblocks/type_admin.php
                    $cls[] = $baseclass . 'Admin';
                    $paths[] = "{$basepath}/{$blockinfo['type']}_admin.php";
                    $dps[] = "{$basedp}.{$blockinfo['type']}_admin";
                }    
            }
        }
        // blocks/type/type.php | modules/module/xarblocks/type/type.php
        $cls[] = $baseclass;
        $paths[] = "{$basepath}/{$blockinfo['type']}/{$blockinfo['type']}.php";
        $dps[] = "{$basedp}.{$blockinfo['type']}.{$blockinfo['type']}";
        if (!empty($blockinfo['module'])) {
            // modules/module/xarblocks/type.php
            $cls[] = $baseclass;
            $paths[] = "{$basepath}/{$blockinfo['type']}.php";
            $dps[] = "{$basedp}.{$blockinfo['type']}";
        }

        foreach ($paths as $i => $filepath) {
            if (!file_exists($filepath)) continue;
            sys::import($dps[$i]);
            $classname = $cls[$i];
            break;
        }
        
        if (empty($classname))
            throw new FileNotFoundException($filepath);
        
        if (!class_exists($classname))
            throw new ClassNotFoundException($classname);
        
        if (!empty($method) && !method_exists($classname, $method))
            throw new FunctionNotFoundException($classname.'::'.$method);
        
        // Load the block language files        
        // What to do here? return doesnt seem right
        if (!xarMLS::loadTranslations($filepath))
            return;
        
        $object = new $classname($blockinfo);

        return $object;
        
    }
/**
 * Helper function used by block subsystem to call a block method suitabled for rendering
 *
 * @author Chris Powis 
 * 
 * @param  object $block the block instance supplying the method
 * @param  string $method, name of the method to call
 * @return string output the block to show
 * @throws  BAD_PARAM, DATABASE_ERROR, ID_NOT_EXIST, MODULE_FILE_NOT_EXIST
 */        
    public static function guiMethod(iBlock $block, $method, $block_tpl=null)
    {
        if (!method_exists($block, $method)) 
            throw new FunctionNotFoundException($method);
        
        $tplData = $block->$method();
        if (is_array($tplData)) {
            // handler for legacy block display methods returning tpl data in $content
            // @todo remove when all module blocks are updated
            if ($method == 'display' && isset($tplData['content']))
                $tplData = $tplData['content']; 
            // inject blocklayout info 
            $tplData['_bl_block_id']       = $block->block_id;
            $tplData['_bl_block_name']     = $block->name;
            $tplData['_bl_block_type']     = $block->type;
            $tplData['_bl_block_type_id']  = $block->type_id;
            $tplData['_bl_block_group']    = $block->group;
            $tplData['_bl_block_group_id'] = $block->group_id;

            // Legacy (deprecated)
            // @TODO: remove these once all block templates are using the _bl_ variables
            $tplData['blockid'] = $tplData['bid'] = $block->block_id;
            $tplData['blockname'] = $block->name;
            $tplData['blocktypename'] = $block->type;
            // The block may not be rendered as part of a group.
            $tplData['blockgid'] = $block->group_id;
            $tplData['blockgroupname'] = $tplData['group'] = $block->group;
           
            if ($method != 'display') {
                if (empty($block_tpl))
                    $block_tpl = $method . '-' . $block->type;
                $block->setTemplateBase($block_tpl);
                $block->setBlockTemplate(null);
            }
            return xarTpl::block(
                $block->module, $block->type, $tplData, $block->block_template, $block->template_base, $block->tplmodule);
        } elseif (!empty($tplData) && is_string($tplData)) {
            return $tplData;
        } else {
            return '';
        }
        
    }
/**
 * Helper function used by block subsystem to check if a block explicitly declared a method
 *
 * @author Chris Powis 
 * 
 * @param  object $block the block instance supplying the method
 * @param  string $method, name of the method to check
 * @param  bool $strict, flag to indicate if the block must have declared the method
 * @return bool
 * @throws none
 */ 
    public static function hasMethod(iBlock $block, $method, $strict=false)
    {
        $hasMethod = method_exists($block, $method);
        // if not strict or method not exist, return
        if (!$strict || !$hasMethod) 
            return $hasMethod;

        // strict checks that this class and not one of its parents declared it 
        $refObject  = new ReflectionClass($block);
        $baseClass = !empty($block->module) ?
                     ucfirst($block->module).'_'.ucfirst($block->type).'Block' :
                     ucfirst($block->type).'Block';
        if ($refObject->hasMethod($method)) {
            $methodObject = $refObject->getMethod($method);
            $hasMethod = ( ($methodObject->class === $refObject->getName()) ||
                           (stripos($methodObject->class, $baseClass) === 0) );
        } else {
            $hasMethod = false;
        }
        unset($refObject, $methodObject);     

        return $hasMethod;        
    }
/**
 * Renders a single block
 *
 * @author John Cox
 * 
 * @param  string args[instance] id or name of block instance to render
 * @param  string args[module] module that owns the block
 * @param  string args[type] module that owns the block
 * @return string
 * @todo   this function calls a module function, keep an eye on it.
 */
    public static function renderBlock(Array $args=array())
    {
        // All the hard work is done in this function.
        // It keeps the core code lighter when standalone blocks are not used.
        if (isset($args['instance']))  // valid block instance states
            $args['state'] = array(xarBlock::BLOCK_STATE_VISIBLE, xarBlock::BLOCK_STATE_HIDDEN);
        $args['type_state'] = array(xarBlock::TYPE_STATE_ACTIVE); // valid block type states
        // get block info
        try {
            $blockinfo = xarMod::apiFunc('blocks', 'blocks', 'getinfo', $args);
            return self::render($blockinfo);
        } catch (Exception $e) { return ''; }
    }
/**
 * Renders a block group
 *
 * @author Paul Rosania
 * @author Marco Canini <marco@xaraya.com>
 * 
 * @param string groupname the name of the block group
 * @param string template optional template to apply to all blocks in the group
 * @return string
 * @throws EmptyParameterException
 */
    public static function renderGroup($groupname, $template=null)
    {
        if (empty($groupname)) throw new EmptyParameterException('groupname');
        return self::renderBlock(array('instance' => $groupname, 'box_template' => $template));
    }

    /**
     * Check access for a specific action on block level (see also xarMod and xarObject)
     *
     * @param block object the block we want to check access for
     * @param action string the action we want to take on this block (display/modify/delete)
     * @param roleid mixed override the current user or null
     * @return boolean true if access
     */
    static function checkAccess(iBlock $block, $action, $roleid = null)
    {
        // TODO: support $roleid there someday ?
        return $block->checkAccess($action);
    }
}
?>