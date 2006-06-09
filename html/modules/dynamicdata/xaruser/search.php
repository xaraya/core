<?php
/**
 * Search dynamic data
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * search dynamicdata (called as hook from search module, or directly with pager)
 *
 * @param int q the query
 * @param int startnum
 * @param dd_check
 * @param int numitems The number of items to get
 * @return array output
 */
function dynamicdata_user_search($args)
{
// Security Check
    if(!xarSecurityCheck('ViewDynamicData')) return;

    $data = array();

    if (!xarVarFetch('q', 'isset', $q, NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('dd_check', 'isset', $dd_check, NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('startnum', 'int:0', $startnum,  NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('numitems', 'int:0', $numitems,  NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (empty($dd_check)) {
        $dd_check = array();
    }

    // see if we're coming from the search hook or not
    if (isset($args['objectid'])) {
        $data['ishooked'] = 1;
    } else {
        $data['ishooked'] = 0;
        $data['q'] = isset($q) ? xarVarPrepForDisplay($q) : null;

        if(!xarVarFetch('modid',    'int',   $modid,     NULL, XARVAR_DONT_SET)) {return;}
        if(!xarVarFetch('itemtype', 'int',   $itemtype,  NULL, XARVAR_DONT_SET)) {return;}
        if (empty($modid) && empty($itemtype)) {
            $data['gotobject'] = 0;
        } else {
            $data['gotobject'] = 1;
        }
        if (empty($modid)) {
            $modid = xarModGetIDFromName('dynamicdata');
        }
        if (empty($itemtype)) {
            $itemtype = 0;
        }
    }
    if (!isset($startnum)) {
        $startnum = 1;
    }
    if (!isset($numitems)) {
        $numitems = 20;
    }

    if (empty($data['ishooked']) && !empty($data['gotobject'])) {
        // get the selected object
        $objects = array();
        $object = xarModAPIFunc('dynamicdata','user','getobjectinfo',
                                array('moduleid' => $modid,
                                      'itemtype' => $itemtype));
        if (!empty($object)) {
            $objects[$object['objectid']] = $object;
        }
    } else {
        // get items from the objects table
        $objects = xarModAPIFunc('dynamicdata','user','getobjects');
    }

    $data['items'] = array();
    $mymodid = xarModGetIDFromName('dynamicdata');
    if ($data['ishooked']) {
        $myfunc = 'view';
    } else {
        $myfunc = 'search';
    }
    foreach ($objects as $itemid => $object) {
        // skip the internal objects
        if ($itemid < 3) continue;
        $modid = $object['moduleid'];
        // don't show data "belonging" to other modules for now
        if ($modid != $mymodid) {
            continue;
        }
        $label = $object['label'];
        $itemtype = $object['itemtype'];
        $fields = xarModAPIFunc('dynamicdata','user','getprop',
                                array('modid' => $modid,
                                      'itemtype' => $itemtype));
        $wherelist = array();
        foreach ($fields as $name => $field) {
            if (!empty($dd_check[$field['id']])) {
                $fields[$name]['checked'] = 1;
                if (!empty($q)) {
                    $wherelist[] = $name . " LIKE '%" . $q . "%'";
                }
            }
        }
        if (!empty($q) && count($wherelist) > 0) {
            $where = join(' or ',$wherelist);
            $status = 1;
            $pagerurl = xarModURL('dynamicdata','user','search',
                                  array('modid' => ($modid == $mymodid) ? null : $modid,
                                        'itemtype' => empty($itemtype) ? null : $itemtype,
                                        'q' => $q,
                                        'dd_check' => $dd_check));
            $result = xarModAPIFunc('dynamicdata','user','showview',
                                    array('modid' => $modid,
                                          'itemtype' => $itemtype,
                                          'where' => $where,
                                          'startnum' => $startnum,
                                          'numitems' => $numitems,
                                          'pagerurl' => $pagerurl,
                                          'layout' => 'list',
                                          'status' => $status));
        } else {
            $result = null;
        }
        // nice(r) URLs
        if ($modid == $mymodid) {
            $modid = null;
        }
        if ($itemtype == 0) {
            $itemtype = null;
        }
        $data['items'][] = array(
                                 'link'     => xarModURL('dynamicdata','user',$myfunc,
                                                         array('modid' => $modid,
                                                               'itemtype' => $itemtype)),
                                 'label'    => $label,
                                 'modid'    => $modid,
                                 'itemtype' => $itemtype,
                                 'fields'   => $fields,
                                 'result'   => $result,
                                );
    }

    return $data;
}

?>
