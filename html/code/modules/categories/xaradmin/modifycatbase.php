<?php

/**
 * udpate item from categories_admin_modify
 */
function categories_admin_modifycatbase()
{
    if (!xarVarFetch('bid', 'id', $bid, NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('modid', 'id', $modid, NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('itemtype', 'id', $itemtype, NULL, XARVAR_NOT_REQUIRED)) {return;}

    $data = array();

    if (!empty($bid)) {
        // Editing an existing category base.

        // Security check
        // TODO: category links - what security check is needed here? AdminCategoryLink? Check for base id?
        if (!empty($itemtype)) {
            $modtype = $itemtype;
        } else {
            $modtype = 'All';
        }
        if(!xarSecurityCheck('DeleteCategoryLink', 1, 'Link', "$modid:$modtype:All:All")) {return;}

        $data['catbase'] = xarMod::apiFunc(
            'categories', 'user', 'getcatbase',
            array(
                'bid' => $bid,
                'modid' => $modid, // temporary
                'itemtype' => $itemtype // temporary
            )
        );

        // Form item for choosing the base category.
        $data['cidselect'] = xarMod::apiFunc(
            'categories', 'visual', 'makeselect',
            array(
                'values' => array($data['catbase']['cid'] => 1),
                'multiple' => false
            )
        );

        $data['func'] = 'modify';

        $data['bid'] = $bid;
        $data['modid'] = $modid;
        $data['itemtype'] = $itemtype;

        if (empty($module) && !empty($modid) && is_numeric($modid)) {
            $modinfo = xarMod::getInfo($modid);
            $module = $modinfo['name'];
        }
        $data['module'] = $module;

        // TODO: could do with this in the template, but there is no way to add it yet.
        xarMod::apiFunc('base', 'javascript', 'moduleinline',
            array(
                'position' => 'head',
                'code' => 'xar_base_reorder_warn = \'' . xarML('You must select the category base to move.') . '\''
            )
        );

        // Get count of category bases in this group (for module/itemtype)
        $data['groupcount'] = xarMod::apiFunc(
            'categories', 'user', 'countcatbases',
            array('modid' => $modid, 'itemtype' => $itemtype)
        );

        // Get the list of cat bases for the order list.
        $data['catbases'] = xarMod::apiFunc(
            'categories', 'user', 'getallcatbases',
            array('modid' => $modid, 'itemtype' => $itemtype, 'order' => 'order')
        );
        
        // TODO: config hooks for the category base and modify hooks for the category base item

    } else {
/*
        // Adding a new Category Base
        // TODO...

        if(!xarSecurityCheck('AddCategoryLink')) {return;}
*/
    }

    // Return output
    return($data);
}

?>
