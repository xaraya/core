<?php
/**
 * Dynamic data view items
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * view items
 */
function dynamicdata_admin_view($args)
{
    extract($args);

    if(!xarVarFetch('itemid',   'int',   $itemid,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('modid',    'int',   $modid,     xarModGetIDFromName('dynamicdata'), XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemtype', 'int',   $itemtype,  0, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('startnum', 'int',   $startnum,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('numitems', 'int',   $numitems,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('sort',     'isset', $sort,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('join',     'isset', $join,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',    'isset', $table,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('catid',    'isset', $catid,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('layout',   'str:1' ,$layout,    'default', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('tplmodule','isset', $tplmodule, 'dynamicdata', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('template', 'isset', $template,  NULL, XARVAR_DONT_SET)) {return;}

    $object = xarModAPIFunc('dynamicdata','user','getobjectlist',
                            array('objectid'  => $itemid,
                                  'moduleid'  => $modid,
                                  'itemtype'  => $itemtype,
                                  'join'      => $join,
                                  'table'     => $table,
                                  'tplmodule' => $tplmodule,
                                  ));
    if (!isset($object)) {
        return;
    }

    $data = $object->toArray();
    // TODO: remove this when we turn all the moduleid into modid
    $data['modid'] = $data['moduleid'];
    // TODO: another stray
    $data['catid'] = $catid;
    $data = array_merge($data,xarModAPIFunc('dynamicdata','admin','menu'));

    // Security check - important to do this as early as possible to avoid
    // potential security holes or just too much wasted processing
// Security Check
    if(!xarSecurityCheck('EditDynamicData')) return;

    // show other modules
    $data['modlist'] = array();
    if ($data['objectid'] == 1 && empty($table)) {
        $objects = xarModAPIFunc('dynamicdata','user','getobjects');
        xarLogMessage('AFTER getobjects');
        $seenmod = array();
        foreach ($objects as $object) {
            $seenmod[$object['moduleid']] = 1;
        }

        $modList = xarModAPIFunc('modules','admin','getlist',
                          array('orderBy'     => 'category/name'));
        $oldcat = '';
        for ($i = 0, $max = count($modList); $i < $max; $i++) {
            if (!empty($seenmod[$modList[$i]['regid']])) {
                continue;
            }
            if (isset($modList[$i]['category']) && $oldcat != $modList[$i]['category']) {
                $modList[$i]['header'] = xarVarPrepForDisplay($modList[$i]['category']);
                $oldcat = $modList[$i]['category'];
            } else {
                $modList[$i]['header'] = '';
            }
            if(xarSecurityCheck('AdminDynamicDataItem',0,'Item',$modList[$i]['regid'].':All:All')) {
                $modList[$i]['link'] = xarModURL('dynamicdata','admin','modifyprop',
                                                  array('modid' => $modList[$i]['regid']));
            } else {
                $modList[$i]['link'] = '';
            }
            $data['modlist'][] = $modList[$i];
        }
    }

    if (xarSecurityCheck('AdminDynamicData',0)) {
        if (!empty($data['table'])) {
            $data['querylink'] = xarModURL('dynamicdata','admin','query',
                                           array('table' => $data['table']));
        } elseif (!empty($data['join'])) {
            $data['querylink'] = xarModURL('dynamicdata','admin','query',
                                           array('itemid' => $objectid,
                                                 'join' => $data['join']));
        } else {
            $data['querylink'] = xarModURL('dynamicdata','admin','query',
                                           array('itemid' => $data['objectid']));
        }
    }

    if (file_exists('modules/' . $data['tplmodule'] . '/xartemplates/admin-new.xd') ||
        file_exists('modules/' . $data['tplmodule'] . '/xartemplates/admin-new-' . $data['template'] . '.xd')) {
        return xarTplModule($data['tplmodule'],'admin','view',$data,$data['template']);
    } else {
        return xarTplModule('dynamicdata','admin','view',$data);
    }
}

?>
