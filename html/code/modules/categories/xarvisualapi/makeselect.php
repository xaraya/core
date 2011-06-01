<?php

/**
 * Make a &lt;select&gt; box with tree of categories (&#160;&#160;--+ style)
 * e.g. for use in your own admin pages to select root categories for your
 * module, choose a particular subcategory for an item etc.
 *
 *  -- INPUT --
 * @param $args['cid'] optional ID of the root category used for the tree
 *                     (if not specified, the whole tree is shown)
 * @param $args['eid'] optional ID to exclude from the tree (probably not
 *                     very useful in this context)
 * @param $args['multiple'] optional flag (1) to have a multiple select box
 * @param $args['values'] optional array $values[$id] = 1 to mark option $id
 *                        as selected
 * @param $args['return_itself'] include the cid itself (default false)
 * @param $args['select_itself'] allow selecting the cid itself if included (default false)
 * @param $args['show_edit'] show edit link for current selection (default false)
 * @param $args['javascript'] add onchange, onblur or whatever javascript to select (default empty)
 * @param $args['size'] optional size of the select field (default empty)
 * @param $args['name_prefix'] optional prefix for the select field name (default empty)
 *
 *  -- OUTPUT --
 * @returns string
 * @return select box for categories :
 *
 * &lt;select name="cids[]"&gt; (or &lt;select name="cids[]" multiple&gt;)
 * &lt;option value="123"&gt;&#160;&#160;--+&#160;My Cat 123
 * &lt;option value="124" selected&gt;&#160;&#160;&#160;&#160;+&#160;My Cat 123
 * ...
 * &lt;/select&gt;
 *
 */
function categories_visualapi_makeselect ($args)
{
    // Getting categories Array
    $args['categories'] = xarMod::apiFunc('categories', 'user', 'getcat',
                          array('eid' => (isset($args['eid']))?$args['eid']:false,
                                'cid' => (isset($args['cid']))?$args['cid']:false,
                                'return_itself' => (isset($args['return_itself']))?$args['return_itself']:false,
                                'getchildren' => true,
                                'maximum_depth' => isset($args['maximum_depth'])?$args['maximum_depth']:null,
                                'minimum_depth' => isset($args['minimum_depth'])?$args['minimum_depth']:null));

    if ($args['categories'] === false) {// If it returned false
        $msg = xarML('Error obtaining category.');
        throw new BadParameterException(null, $msg);
    }

    if (!isset($args['multiple'])) {
        $args['multiple'] = 0;
    }

    if (empty($args['show_edit']) || !empty($args['multiple'])) {
        $args['show_edit'] = 0;
    }

    if (!isset($args['name_prefix'])) {
        $args['name_prefix'] = '';
    }
    if (!isset($args['javascript'])) {
        $args['javascript'] = '';
    }

    if (isset($args['template'])) {
        $template = $args['template'];
    } else {
        $template = null;
    }

// Note : $args['values'][$id] will be updated inside the template, so that when several
//        select boxes are used with overlapping trees, categories will only be selected once
// This requires that the values are passed by reference : $args['values'] =& $seencids;
    if (isset($args['values'])) {
        $GLOBALS['Categories_MakeSelect_Values'] =& $args['values'];
    }

    return xarTplModule('categories','visual','makeselect',
                        $args, $template);
}

?>
