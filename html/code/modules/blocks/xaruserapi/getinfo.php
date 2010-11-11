<?php
/**
 * Get details suitable for *rendering* a block instance.
 *
 * @package modules
 * @subpackage blocks module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */
/*
 * Get details suitable for *rendering* a block instance.
 * This will return the details for a block.
 *
 * TODO: big changes planned for in here.
 * - block can be an instance or a 'one-off' standalone block
 * - standalone blocks will be seeded with the block init details (if available)
 * - arbitrary parameters can be passed in to override the block content array elements
 * - some sort of validation check could be made available for the overridable params?
 * - system-level flag to switch between reporting attribute/args errors or just ignoring
 * @author Jim McDonald
 * @author Paul Rosania
 *
 * TODO: move this function to a method of the xarBlock class,
 * per note below, there's no reason for this to be here, it doesn't,
 * contrary to the notes in the xarBlock_renderBlock method, lighten
 * core in any way, since blocks module is also part of core. We reduced
 * the size of the core blocks.php file by moving the blockgroup handling to a block
 * Note: this function is used solely by the BL renderer, and is subject
 * to change without notice.
 *
 * @param array   $args array of parameters
 */

function blocks_userapi_getinfo(Array $args=array())
{
    extract($args);

    // Exit now for templates that have not been recompiled - at least one of these elements
    // will be missing.
//    if (!isset($args['instance']) || !isset($args['module']) || !isset($args['type'])) {
    if (!(isset($args['instance']) || !(isset($args['module']) && isset($args['type'])))) {
        return;
    }

    // TODO: security check on the block name here, if $name or $instance is set.

    // We will be selecting either a block instance or a stand-alone block.
    if (!empty($instance)) {
        // Block instance - fetch it from the database.
        if (is_numeric($instance)) {
            if (xarVarIsCached('Block.Infos2', $instance)) {
                $blockinfo = xarVarGetCached('Block.Infos2', $instance);
            } else {
                $blockinfo = xarMod::apiFunc('blocks', 'user', 'get', array('bid' => $instance));
            }

            if (empty($blockinfo)) {
                // No matching block was found.
                return;
            }

            if (!xarVarIsCached('Block.Infos2', $blockinfo['bid'])) {
                // Cache the block details if available
                xarVarSetCached('Block.Infos2', $blockinfo['bid'], $blockinfo);
            }

            // TODO: return here if the block name fails a security check.
        } else {
            $blockinfo = xarMod::apiFunc('blocks', 'user', 'get', array('name' => $instance));
        }
    } else {
        // Standalone block - load it from file and seed with default details.
        $blockinfo = array(
            'module' => $module,
            'type' => $type,
            'title' => '',
            'template' => '',
            'bid' => 0,
            'state' => 3,
            'name' => (!is_null($name) ? $name : '')
        );

        // Get block default content.
        $default_content = xarMod::apiFunc(
            'blocks', 'user', 'read_type_init',
            array('module' => $module, 'type' => $type)
        );

        if (!empty($default_content)) {
            // Default details for the block are available - use them.
            // Note that this will be an array, not serialized. Blocks
            // that provide initialization data must also be able to
            // accept a non-serialized content array.
            $blockinfo['content'] = $default_content;
        } else {
            $blockinfo['content'] = '';
        }
    }

    // No block details.
    // Perhaps the instance or module/type is invalid.
    // FIXME: Invalid module/type won't mean empty blockinfo here.
    if (empty($blockinfo)) {return;}

    // Do standard overrides.
    if (!empty($title)) {$blockinfo['title'] = $title;}
    if (!empty($state)) {$blockinfo['state'] = $state;}

    // Now do the custom overrides.
    // We have a hack here to unserialize the content string, update the
    // fields then reserialize (if it started serialized). The problem is,
    // until ALL blocks can accept arrays as content, then passing arrays
    // from here will cause blocks to fail. This hack will work until all
    // blocks are converted.

    // Not sure how this will work - there is no reliable way of recognising
    // a string of serialised data. We will make a guess that is starts
    // 'a:[number]:{' and ends '}'.
    $serialize_flag = false;
    if (is_string($blockinfo['content']) && preg_match('/^[at]:[0-9]+:\{.*\}$/', $blockinfo['content'])) {
        // We think this is serialized data - try expanding it.
        $content2 = @unserialize($blockinfo['content']);
        if (!is_null($content2)) {
            // Looks like it was successful.
            $blockinfo['content'] =& $content2;
            $serialize_flag = true;
        }
    }

    // The content at this point will be, completely empty, a string or
    // an array - only try and set array elements for an array.
    // FIXME: the over-rides here come from $args, not from $blockinfo[content]
    if (is_array($blockinfo['content'])) {
        foreach($blockinfo['content'] as $pname => $pvalue) {
            // not allowed to over-ride block version in block tags
            if ($pname == 'xarversion') continue;
            // If the array element exists, then override it.
            // There is some validation here - so arrays can't
            // override strings and strings can't override arrays.
            // TODO: allow a block to provide validation rules to
            // pass $pvalue through for each $pname.
            // Such validation would also be able to convert numbers
            // into booleans, string lists into arrays etc.
            // Only override non-array and unset elements for now.
            if (isset($args[$pname]) && !is_array($pvalue) && $args[$pname] !== $pvalue) {
                // Only override non-array elements
                switch (gettype($blockinfo['content'][$pname])) {
                    case 'integer' : $valid = xarVarValidate('int', $args[$pname], true); break;
                    case 'string' : $valid = xarVarValidate('str', $args[$pname], true); break;
                    // Note: bool type validates 'true'/'false', or set/non-set
                    // but NOT non-zero and zero.
                    case 'boolean' : $valid = xarVarValidate('bool', $args[$pname], true); break;
                    case 'float' : $valid = xarVarValidate('float', $args[$pname], true); break;
                    default : $valid = false;
                }
                // If the override validated, then set the parameter.
                if ($valid) {$blockinfo['content'][$pname] = $args[$pname];}
            }
        }
        foreach ($args as $aname => $avalue) {
            if (isset($blockinfo['content'][$aname]) || is_array($avalue)) continue;
            // Only override unset and non-array elements
            $blockinfo['content'][$aname] = $avalue;
        }

        if ($serialize_flag) {
            // We need to serialize the content again.
            // TODO: when all blocks support serialized content data,
            // remove this re-serialization. It is just for legacy support.
            $blockinfo['content'] = @serialize($blockinfo['content']);
        }
    }

    if (empty($template)) {$template = $blockinfo['template'];}
    // Split template name into outer and inner using the ';' separator.
    if (strpos($template, ';') === FALSE) {
        $blockinfo['_bl_box_template'] = $template;
        $blockinfo['_bl_block_template'] = '';
    } else {
        $template = explode(';', $template, 3);
        $blockinfo['_bl_box_template'] = $template[0];
        $blockinfo['_bl_block_template'] = $template[1];
    }

    // Allow a global override to the box template for the xar:blockgroup tag.
    if (!empty($box_template) && empty($blockinfo['_bl_box_template'])) {$blockinfo['_bl_box_template'] = $box_template;}

    // Legacy support.
    $instance['id'] = $blockinfo['bid'];
    $blockinfo['bkey'] = $blockinfo['bid'];
    $blockinfo['mid'] = $blockinfo['module'];

    return $blockinfo;
}

?>
