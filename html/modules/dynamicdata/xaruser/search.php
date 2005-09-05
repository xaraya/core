<?php
/**
 * File: $Id$
 *
 * Search
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
*/
/**
 * the main user function lists the available objects defined in DD
 *
 */
function dynamicdata_user_search()
{
// Security Check
	if(!xarSecurityCheck('ViewDynamicData')) return;

    $data = xarModAPIFunc('dynamicdata','user','menu');

    if (!xarModAPILoad('dynamicdata','user')) return;

    if (!xarVarFetch('q', 'isset', $q, NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('dd_check', 'isset', $dd_check, NULL, XARVAR_DONT_SET)) {return;}
    if (empty($dd_check)) {
        $dd_check = array();
    }

    // get items from the objects table
    $objects = xarModAPIFunc('dynamicdata','user','getobjects');

    $data['items'] = array();
    $mymodid = xarModGetIDFromName('dynamicdata');
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
            $numitems = 20;
            $status = 1;
            $result = xarModAPIFunc('dynamicdata','user','showview',
                                    array('modid' => $modid,
                                          'itemtype' => $itemtype,
                                          'where' => $where,
                                          'numitems' => $numitems,
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
                                 'link'   => xarModURL('dynamicdata','user','view',
                                                       array('modid' => $modid,
                                                             'itemtype' => empty($itemtype) ? null : $itemtype)),
                                 'label'  => $label,
                                 'fields' => $fields,
                                 'result' => $result,
                                );
    }
    return $data;
}

?>