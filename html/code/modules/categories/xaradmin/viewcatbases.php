<?php

/**
 * create item from xarMod::guiFunc('categories','admin','viewcat')
 */
function categories_admin_viewcatbases()
{
    // Get parameters
    // TODO: add pager
    if (!xarVarFetch('modid', 'id', $modid,  NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('itemtype', 'int:0', $itemtype,  NULL, XARVAR_NOT_REQUIRED)) {return;}

    // Security check
    if (!xarSecurityCheck('ReadCategories')) {return;}

    // These two variables define the scope of this screen.
    $data = array(
        'modid' => $modid,
        'itemtype' => $itemtype
    );

    // TODO: add pager
    $data['catbases'] = xarMod::apiFunc(
        'categories', 'user', 'getallcatbases',
        array(
            'modid' => $modid,
            'itemtype' => $itemtype,
            'format' => 'flat',
            'order' => 'module,itemtype'
        )
    );

    // Get itemtype names for all modules selected (where available).
    $itemtypes = array();
    if (!empty($data['catbases'])) {
        foreach ($data['catbases'] as $itemtypekey => $catbase) {
            if (empty($itemtypes[$catbase['modid']])) {
                $itemtypes[$catbase['modid']] = xarMod::apiFunc(
                    $catbase['module'], 'user', 'getitemtypes',
                    array(), 0
                );
            }

            if (!empty($itemtypes[$catbase['modid']][$catbase['itemtype']])) {
                $data['catbases'][$itemtypekey]['itemtypename'] =  $itemtypes[$catbase['modid']][$catbase['itemtype']]['label'];
            }
        }
    }

    return $data;
}

?>
