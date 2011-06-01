<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Jim McDonald
// Purpose of file: Categories Navigation Block
// ----------------------------------------------------------------------


/**
 * modify block settings
 */
function categories_navigationblock_modify($blockinfo)
{
    // Get current content
    if (!is_array($blockinfo['content'])) {
        $vars = @unserialize($blockinfo['content']);
    } else {
        $vars = $blockinfo['content'];
    }

    // Defaults
    if (empty($vars['layout'])) {
        $vars['layout'] = 1;
    }
    if (empty($vars['showcatcount'])) {
        $vars['showcatcount'] = 0;
    }
    if (empty($vars['showchildren'])) {
        $vars['showchildren'] = 0;
    }
    if (empty($vars['startmodule'])) {
        $vars['startmodule'] = '';
    }
    if (empty($vars['showempty'])) {
        $vars['showempty'] = 0;
    }
    if (empty($vars['dynamictitle'])) {
        $vars['dynamictitle'] = 0;
    }

    $vars['modules'] = array();
    $vars['modules'][] = array('id' => '',
                               'name' => xarML('Adapt dynamically to current page'));

    // List contains:
    // 0. option group for the module
    // 1. module [base1|base2]
    // 2.    module [base1]    (for itemtype 0)
    //       module [base2]
    // 3.    module:itemtype [base3|base4]
    // 4.       itemtype [base3]
    //          itemtype [base4]

    $allcatbases = xarMod::apiFunc(
        'categories', 'user', 'getallcatbases',
        array('order'=>'module', 'format'=>'tree')
    );

    foreach($allcatbases as $modulecatbases) {
        // Module label for the option group in the list.
        $modlabel = xarML('#(1)', ucwords($modulecatbases['module']));

        $vars['modules'][] = array(
            'label' => $modlabel
        );

        $indent = '&#160;&#160;&#160;';

        foreach($modulecatbases['itemtypes'] as $thisitemtype => $itemtypecatbase) {
            if (!empty($itemtypecatbase['catbases'])) {
                $catlist = '[';
                $join = '';
                foreach($itemtypecatbase['catbases'] as $itemtypecatbases) {
                    $catlist .= $join . $itemtypecatbases['category']['name'];
                    $join = ' | ';
                }
                $catlist .= ']';

                //if (empty($itemtypecatbase['itemtype']['label'])) {
                if ($thisitemtype == 0) {
                    // Default module cats at top level.
                    $indent_level = 0;
                    $itemtypelabel = '';
                } else {
                    // Item types at one level deeper
                    $indent_level = 1;
                    $itemtypelabel = ' -&gt; ' . xarML('#(1)', $itemtypecatbase['itemtype']['label']);
                }

                // Module-Itemtype [all cats]
                $vars['modules'][] = array(
                    'id' => $modulecatbases['module'] . '.' . $thisitemtype . '.0',
                    'name' => str_repeat($indent, $indent_level) . $modlabel . $itemtypelabel . ' ' . $catlist
                );

                // Individual categories a level deeper.
                $indent_level += 1;

                // Individual base categories where there are more than one.
                if (count($itemtypecatbase['catbases']) > 1) {
                    foreach($itemtypecatbase['catbases'] as $itemtypecatbases) {
                        $catlist = '[' . $itemtypecatbases['category']['name'] . ']';
                        if ($thisitemtype == 0) {$itemtypelabel = $modlabel;}
                        $vars['modules'][] = array(
                            'id' => $modulecatbases['module'] . '.' . $thisitemtype . '.' . $itemtypecatbases['category']['cid'],
                            'name' => str_repeat($indent, $indent_level) . $itemtypelabel . ' ' . $catlist
                        );
                    }
                }
            }
        }
    }

    $vars['blockid'] = $blockinfo['bid'];
    // Return output
    return xarTplBlock('categories', 'nav-admin', $vars);
}

/**
 * update block settings
 */
function categories_navigationblock_update($blockinfo)
{
    $vars = array();
    if(!xarVarFetch('layout',       'isset', $vars['layout'],       NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('showcatcount', 'isset', $vars['showcatcount'], NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('showchildren', 'isset', $vars['showchildren'], NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('showempty',    'checkbox', $vars['showempty'],    false, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('startmodule',  'isset', $vars['startmodule'],  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('dynamictitle', 'checkbox', $vars['dynamictitle'], false, XARVAR_DONT_SET)) {return;}

    $blockinfo['content'] = $vars;

    return $blockinfo;
}

/**
 * built-in block help/information system.
 */
function categories_navigationblock_help()
{
    return '';
}

?>