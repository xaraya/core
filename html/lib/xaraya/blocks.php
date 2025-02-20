<?php
/**
 * Display Blocks
 * *
 * @package core\blocks
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Paul Rosania
 * @author Chris Powis
 */

sys::import("xaraya.context.context");
sys::import('xaraya.facades.caching');
sys::import('xaraya.facades.database');
sys::import('xaraya.facades.modules');
use Xaraya\Context\Context;
use Xaraya\Facades\xarCache3;
use Xaraya\Facades\xarDB3;
use Xaraya\Facades\xarMod3;

interface ixarBlock
{
    const TYPE_STATE_ACTIVE = 1; 
    const TYPE_STATE_MISSING = 2;
    const TYPE_STATE_ERROR = 3;    
    const TYPE_STATE_MOD_UNAVAILABLE = 4; 

    const BLOCK_STATE_HIDDEN = 0;   // Hidden blocks still execute, they just don't render
    const BLOCK_STATE_INACTIVE = 1; // Inactive blocks don't execute, don't render
    const BLOCK_STATE_VISIBLE = 2;

    public static function render(array $data = [], $context = null);
    public static function renderBlock(array $args = [], $context = null);
    public static function renderGroup($groupname, $template = null, $context = null);
    public static function hasMethod(iBlockType $block, $method, $strict = false);
    public static function guiMethod(iBlock $block, $method);
    public static function checkAccess(iBlock $block, $action, $roleid = null);

}

/**
 * Display Blocks
 * *
 * @package core\blocks
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Paul Rosania
 * @author Chris Powis
 */
class xarBlock extends xarObject implements ixarBlock
{
    protected static bool $initialized = false;

    private function __construct()
    {}
/**
 * Initialize blocks subsystem
 *
 * @author Paul Rosania
 * 
 * @param  array<string, mixed> $args
 * @return boolean
 */
    public static function init(array $args = array())
    {
        if (empty($args) && self::$initialized) {
            return true;
        }
        // Blocks Support Tables
        sys::import('modules.blocks.xartables');
        // pass along the DB prefix to $tablefunc
        $tables = blocks_xartables(xarDB3::getPrefix());
        xarDB3::importTables($tables);
        self::$initialized = true;
        return true;    
    }

/**
 * Renders a block instance
 *
 * @author Paul Rosania
 * @author Marco Canini <marco@xaraya.com>
 * @author Chris Powis 
 * 
 * @param array<string, mixed> $blockinfo block information parameters
 * @param ?Context<string, mixed> $context optional context for the block call (default = none)
 * @return string output the block to show
 * @todo   this function calls a module function, keep an eye on it.
 */
    public static function render(array $blockinfo = [], $context = null)
    {
        // Get a cache key for this block if it's suitable for block caching
        $cacheKey = xarCache3::getBlockKey($blockinfo);

        // Check if the block is cached
        if (xarCache3::hasBlock($cacheKey)) {
            // Return the cached block output
            return xarCache3::getBlock($cacheKey);
        } 
        if (!isset($context)) {
            $context = new Context(['source' => __METHOD__]);
        }
        
        try {
            // get the block instance
            $block = self::getObject($blockinfo, 'display');
            // set context if available in block render
            $block->setContext($context);

            // check if block expired already
            $now = time();
            if ($block->expire && $now > $block->expire) {
                xarCache3::setBlock($cacheKey, '');
                return '';
            }
            // checkAccess for display method
            if (!$block->checkAccess('display')) {
                xarCache3::setBlock($cacheKey, '');
                if (isset($block->display_access) && $block->display_access['failure']) {
                    // @TODO: render to an error/exception block?
                    return xarTpl::module('privileges','user','errors',
                        array('layout' => 'no_block_privileges'));
                }
                return '';
            }
            // don't render hidden blocks
            if ($block->state == self::BLOCK_STATE_HIDDEN) {
                // just execute the display method and return an empty string
                $block->display();
                xarCache3::setBlock($cacheKey, '');
                return '';
            }
            // render the block 
            $blockinfo['content'] = self::guiMethod($block, 'display');
            // no content, ok, nothing to display 
            if (empty($blockinfo['content'])) {
                xarCache3::setBlock($cacheKey, '');
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
                // Pass along the block context for xarTpl::renderBlockBox() if needed
                $blockinfo['context'] ??= $block->getContext();
                $boxOutput = xarTpl::renderBlockBox($blockinfo, $block->box_template);
            }                      

            // Set the output of the block in cache
            xarCache3::setBlock($cacheKey, $boxOutput);

            return $boxOutput;
            
        } catch (Exception $e) {
            if ((bool) xarMod3::getVar('noexceptions', 'blocks') || !xarUser::isDebugAdmin()) {
                xarCache3::setBlock($cacheKey, '');
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

        // @checkme do we want to foresee anything special for other interfaces, or always let them go through the search process below?
        // @checkme best would be to simply autoload the class, if we do know the actual $classname - otherwise we'll need $filepath too
        if ((empty($interface) || $interface == 'display') && !empty($blockinfo['classname']) && strpos($blockinfo['classname'], '\\') !== false && !empty($blockinfo['filepath']) && file_exists($blockinfo['filepath'])) {
            $classname = $blockinfo['classname'];
            $filepath = $blockinfo['filepath'];
            include_once $filepath;

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

        // @todo use xarClassMap::findBlock() instead

        // $cls does not take into account possible namespace + it does not re-use what blocksapi getinfo() could give
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

        sys::import("xaraya.classmap");
        $result = xarClassMap::findBlockByPath($paths);
        if (!empty($result['filepath']) && !empty($result['found'])) {
            $filepath = $result['filepath'];
            // require the file (raises error if file not found)
            require_once($filepath);
            if (count($result['found']) > 1) {
                // @todo which one do we pick here?
            }
            $classname = array_key_first($result['found']);
            $blockinfo['filepath'] = $filepath;
        } else {
            // we try to get the actual $classname and $filepath here again - at least until after UPGRADE due to table change
            $oldclasses = get_declared_classes();
            foreach ($paths as $i => $filepath) {
                if (!file_exists($filepath)) continue;
                sys::import($dps[$i]);
                $newclasses = get_declared_classes();
                $diffclasses = array_values(array_diff($newclasses, $oldclasses, ['MenuBlock', 'BasicBlock', 'BlockType']));
                // assuming new classes in namespaces only have 1 class definition per file as they should...
                if (count($diffclasses) > 0) {
                    $classname = $diffclasses[0];
                } else {
                    $classname = $cls[$i];
                }
                // we need to set the actual $filepath here before constructing the object
                $blockinfo['filepath'] = $filepath;
                break;
            }
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
 * @param  BasicBlock $block the block instance supplying the method
 * @param  string $method, name of the method to call
 * @return string output the block to show
 * @throws  FunctionNotFoundException
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
            // Pass along the block context for xarTpl::block() if needed
            $tplData['context'] ??= $block->getContext();
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
 * @param  BlockType $block the block instance supplying the method
 * @param  string $method, name of the method to check
 * @param  bool $strict, flag to indicate if the block must have declared the method
 * @return bool
 */ 
    public static function hasMethod(iBlockType $block, $method, $strict=false)
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
 * @param array<string, mixed> $args
 * with
 *     string args[instance] id or name of block instance to render
 *     string args[module] module that owns the block
 *     string args[type] module that owns the block
 * @param ?Context<string, mixed> $context optional context for the block call (default = none)
 * @return string
 * @todo   this function calls a module function, keep an eye on it.
 */
    public static function renderBlock(array $args = [], $context = null)
    {
        // All the hard work is done in this function.
        // It keeps the core code lighter when standalone blocks are not used.
        if (isset($args['instance']))  // valid block instance states
            $args['state'] = array(self::BLOCK_STATE_VISIBLE, self::BLOCK_STATE_HIDDEN);
        $args['type_state'] = array(self::TYPE_STATE_ACTIVE); // valid block type states
        if (!isset($context)) {
            $context = new Context(['source' => __METHOD__]);
        }
        // get block info
        try {
            $blockinfo = xarMod3::apiFunc('blocks', 'blocks', 'getinfo', $args, $context);
            return self::render($blockinfo, $context);
        } catch (Exception $e) {
            if ((bool) xarMod3::getVar('noexceptions', 'blocks') || !xarUser::isDebugAdmin()) {
                // Get a cache key for this block if it's suitable for block caching
                if (!empty($blockinfo)) {
                    $cacheKey = xarCache3::getBlockKey($blockinfo);
                    xarCache3::setBlock($cacheKey, '');
                }
                return '';
            } else {
                throw($e); 
            }
        }
    }
/**
 * Renders a block group
 *
 * @author Paul Rosania
 * @author Marco Canini <marco@xaraya.com>
 * 
 * @todo support context in templates? See xar:blockgroup tag
 * @param string $groupname the name of the block group
 * @param ?string $template optional template to apply to all blocks in the group
 * @param ?Context<string, mixed> $context optional context for the block call (default = none)
 * @return string
 * @throws EmptyParameterException
 */
    public static function renderGroup($groupname, $template = null, $context = null)
    {
        if (empty($groupname)) throw new EmptyParameterException('groupname');
        if (!isset($context)) {
            $context = new Context(['source' => __METHOD__]);
        }
        return self::renderBlock(array('instance' => $groupname, 'box_template' => $template), $context);
    }

    /**
     * Check access for a specific action on block level (see also xarMod and xarObject)
     *
     * @param iBlock $block the block we want to check access for
     * @param string $action the action we want to take on this block (display/modify/delete)
     * @param mixed $roleid override the current user or null
     * @return boolean true if access
     */
    static function checkAccess(iBlock $block, $action, $roleid = null)
    {
        // TODO: support $roleid there someday ?
        return $block->checkAccess($action);
    }
}
