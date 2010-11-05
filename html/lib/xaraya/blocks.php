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
 * @link http://www.xaraya.com
 *
 * @author Paul Rosania
 * @TODO:
 * - system-level flag to switch between reporting or ignoring errors
 * - implement inactive block state when the parent module is inactive
 */
Class xarBlock extends Object implements IxarBlock
{

    const BLOCK_STATE_HIDDEN = 0;   // Hidden blocks still execute, they just don't render
    const BLOCK_STATE_INACTIVE = 1; // Inactive blocks don't execute, don't render
    const BLOCK_STATE_VISIBLE = 2;

/**
 * Initialize blocks subsystem
 *
 * @author Paul Rosania
 * 
 * @param  array args
 * @return bool
 */
    public static function init(&$args)
    {
        // Blocks Support Tables
        $prefix = xarDB::getPrefix();
        $tables = array(
            'block_instances'       => $prefix . '_block_instances',
            'block_group_instances' => $prefix . '_block_group_instances',
            'block_types'           => $prefix . '_block_types'
        );
        xarDB::importTables($tables);
        return true;
    }

/**
 * Renders a block instance
 *
 * @author Paul Rosania
 * @author Marco Canini <marco@xaraya.com>
 * 
 * @param  array data block information parameters
 * @return string output the block to show
 * @throws  BAD_PARAM, DATABASE_ERROR, ID_NOT_EXIST, MODULE_FILE_NOT_EXIST
 * @todo   this function calls a module function, keep an eye on it.
 */
    public static function render(Array $data=array())
    {
        // Skip executing inactive blocks
        if ($data['state'] == xarBlock::BLOCK_STATE_INACTIVE) {
            // @TODO: global flag to raise exceptions
            // if ((bool)xarModVars::get('blocks', 'noexceptions')) return '';
            return '';
        }
        // Get a cache key for this block if it's suitable for block caching
        $cacheKey = xarCache::getBlockKey($data);

        // Check if the block is cached
        if (!empty($cacheKey) && xarBlockCache::isCached($cacheKey)) {
            // Return the cached block output
            return xarBlockCache::getCached($cacheKey);
        }

        xarLogMessage("xarBlock::render: begin $data[module]:$data[type]:$data[name]");

        // This lets the security system know what module we're in
        // no need to update / select in database for each block here
        // TODO: this looks weird
        xarCoreCache::setCached('Security.Variables', 'currentmodule', $data['module']);

        // Attempt to load the block file
        try {
            xarMod::apiFunc('blocks', 'admin', 'load',
                array('module' => $data['module'], 'type' => $data['type'], 'func' => 'display'));
        } catch (Exception $e) {
            // Set the output of the block in cache
            if (!empty($cacheKey)) {
                xarBlockCache::setCached($cacheKey, '');
            }
            if ((bool)xarModVars::get('blocks', 'noexceptions')) {
                return '';
            } else {
                throw($e);
            }
        }

        // @FIXME: class name should be unique
        $className = ucfirst($data['module']) . '_' . ucfirst($data['type']) . 'Block';
        // if we're here, we can safely instantiate the block instance
        $block = new $className($data);

        // check if block expired already
        $now = time();
        if (isset($block->expire) && $now > $block->expire && $block->expire != 0) {
            // Set the output of the block in cache
            if (!empty($cacheKey)) {
                xarBlockCache::setCached($cacheKey, '');
            }
            return '';
        }

        // checkAccess for display method
        if (!$block->checkAccess('display')) {
            // Set the output of the block in cache
            if (!empty($cacheKey)) {
                xarBlockCache::setCached($cacheKey, '');
            }
            if (isset($block->display_access) && $block->display_access['failure']) {
                // @TODO: render to an error/exception block?
                return xarTplModule('privileges','user','errors',array('layout' => 'no_block_privileges'));
            } else {
                return '';
            }
        }

        // now we're safe to call the blocks display method
        try {
            $blockinfo = $block->display();
        } catch (Exception $e) {
            // Set the output of the block in cache
            if (!empty($cacheKey)) {
                xarBlockCache::setCached($cacheKey, '');
            }
            if ((bool)xarModVars::get('blocks', 'noexceptions')) {
                return '';
            } else {
                throw ($e);
            }
        }

        // A block is permitted to return empty, signifying it has nothing to display
        // if it's empty, we have nothing to display either
        if (empty($blockinfo) || !is_array($blockinfo)) {
            // Set the output of the block in cache
            if (!empty($cacheKey)) {
                xarBlockCache::setCached($cacheKey, '');
            }
            return '';
        }

        // Render block if it has content and isn't hidden
        if (is_array($blockinfo['content']) && $data['state'] != xarBlock::BLOCK_STATE_HIDDEN) {
            // Here $blockinfo['content'] is the array of template data
            // which will be passed to the inner block template
            // $blockinfo itself is passed to the outer template
            // Set some additional details that could be useful in the block content.
            // prefix these extra variables (_bl_) to indicate they are supplied by the core.
            $blockinfo['content']['_bl_block_id'] = $blockinfo['bid'];
            $blockinfo['content']['_bl_block_name'] = $blockinfo['name'];
            $blockinfo['content']['_bl_block_type'] = $blockinfo['type'];
            if (!empty($blockinfo['groupid'])) {
                // The block may not be rendered as part of a group.
                $blockinfo['content']['_bl_block_groupid'] = $blockinfo['groupid'];
                $blockinfo['content']['_bl_block_group'] = $blockinfo['group'];
            }
            // Legacy (deprecated)
            // @TODO: remove these once all block templates are using the _bl_ variables
            $blockinfo['content']['blockid'] = $blockinfo['bid'];
            $blockinfo['content']['blockname'] = $blockinfo['name'];
            $blockinfo['content']['blocktypename'] = $blockinfo['type'];
            if (isset($blockinfo['groupid'])) {
                // The block may not be rendered as part of a group.
                $blockinfo['content']['blockgid'] = $blockinfo['groupid'];
                $blockinfo['content']['blockgroupname'] = $blockinfo['group'];
            }

            // Attempt to render this block template data.
            try {
                $blockinfo['content'] = xarTplBlock(
                    $data['module'], $data['type'], $blockinfo['content'],
                    !empty($blockinfo['_bl_block_template']) ? $blockinfo['_bl_block_template'] : NULL,
                    !empty($blockinfo['_bl_template_base']) ? $blockinfo['_bl_template_base'] : NULL
                );
            } catch (Exception $e) {
                // Set the output of the block in cache
                if (!empty($cacheKey)) {
                    xarBlockCache::setCached($cacheKey, '');
                }
                if ((bool)xarModVars::get('blocks', 'noexceptions')) {
                    return '';
                } else {
                    throw ($e);
                }
            }
        } else {
            // hidden block, or no content to display
            // Set the output of the block in cache
            if (!empty($cacheKey)) {
                xarBlockCache::setCached($cacheKey, '');
            }
            return "";
        }

        // Now wrap the block up in a box.
        // TODO: pass the group name into this function (param 2?) for the template path.
        // $blockinfo itself is passed to the outer template
        // Attempt to render this block template data.
        try {
            $boxOutput = xarTpl_renderBlockBox($blockinfo, $data['_bl_box_template']);
        } catch (Exception $e) {
            // Set the output of the block in cache
            if (!empty($cacheKey)) {
                xarBlockCache::setCached($cacheKey, '');
            }
            if ((bool)xarModVars::get('blocks', 'noexceptions')) {
                return '';
            } else {
                throw ($e);
            }
        }

        xarLogMessage("xarBlock::render: end $data[module]:$data[type]:$data[name]");

        // Set the output of the block in cache
        if (!empty($cacheKey)) {
            xarBlockCache::setCached($cacheKey, $boxOutput);
        }

        return $boxOutput;
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
        // @TODO: review getinfo and move it here
        $blockinfo = xarMod::apiFunc('blocks', 'user', 'getinfo', $args);
        // No blockinfo means the block instance specified doesn't exist
        // @CHECKME: optionally raise exception here?
        if (empty($blockinfo)) return '';
        return self::render($blockinfo);
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
     * @return bool true if access
     */
    static function checkAccess($block, $action, $roleid = null)
    {
        // TODO: support $roleid there someday ?
        return $block->checkAccess($action);
    }
}

Interface IxarBlock
{
    public static function render(Array $data=array());
    public static function renderBlock(Array $args=array());
    public static function renderGroup($groupname, $template=null);
}

?>