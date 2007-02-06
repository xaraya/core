<?php
/**
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * View items
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
                                  'template'  => $template,
                                  ));

    if (!isset($object)) {
        return;
    }

    $data = $object->toArray();
    // TODO: remove this when we turn all the moduleid into modid
    $data['modid'] = $data['moduleid'];
    // TODO: another stray
    $data['catid'] = $catid;
    // TODO: is this needed?
    $data = array_merge($data,xarModAPIFunc('dynamicdata','admin','menu'));

    if(!xarSecurityCheck('EditDynamicData')) return;

    if ($data['objectid'] == 1 && empty($table)) {
        $objects = xarModAPIFunc('dynamicdata','user','getobjects');
        xarLogMessage('AFTER getobjects');
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
