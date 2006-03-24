<?php
/**
 * Search
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */

/*
 * Search
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
function roles_user_search()
{
    if(!xarVarFetch('startnum', 'isset', $startnum,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('email',    'isset', $email,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('uname',    'isset', $uname,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('name',     'isset', $name,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('q',        'isset', $q,         NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('bool',     'isset', $bool,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('sort',     'isset', $sort,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('author',   'isset', $author,    NULL, XARVAR_DONT_SET)) {return;}
    $data = array();
    $data['users'] = array();
    // show the search form
    if (!isset($q)) {
        if (xarModIsHooked('dynamicdata','roles')) {
            // get the Dynamic Object defined for this module
            $object = xarModAPIFunc('dynamicdata','user','getobject',
                                     array('module' => 'roles'));
            if (isset($object) && !empty($object->objectid)) {
                // get the Dynamic Properties of this object
                $data['properties'] =& $object->getProperties();
            }
        }
        return $data;
    }

    // execute the search

    // Default parameters
    if (!isset($startnum)) {
        $startnum = 1;
    }
    if (!isset($numitems)) {
        $numitems = 20;
    }

    // Need the database connection for quoting strings.
    $dbconn =& xarDBGetConn();

    // TODO: support wild cards / boolean / quotes / ... (cfr. articles) ?

    // remember what we selected before
    $data['checked'] = array();

    if (xarModIsHooked('dynamicdata','roles')) {
        // make sure the DD classes are loaded
        if (!xarModAPILoad('dynamicdata','user')) return $data;

        // get a new object list for roles
        $object = new Dynamic_Object_List(array('moduleid'  => xarModGetIDFromName('roles')));

        if (isset($object) && !empty($object->objectid)) {
            // save the properties for the search form
            $data['properties'] =& $object->getProperties();

            // quote the search string (in different variations here)
            $quotedlike = $dbconn->qstr('%'.$q.'%');
            $quotedupper = $dbconn->qstr('%'.strtoupper($q).'%');
            $quotedlower = $dbconn->qstr('%'.strtolower($q).'%');
            $quotedfirst = $dbconn->qstr('%'.ucfirst($q).'%');
            $quotedwords = $dbconn->qstr('%'.ucwords($q).'%');

            // run the search query
            $where = array();
            // see which properties we're supposed to search in
            foreach (array_keys($object->properties) as $field) {
                if (!xarVarFetch($field, 'checkbox', $checkfield,  NULL, XARVAR_NOT_REQUIRED)) {return;}
                if ($checkfield) {
                    $where[] = $field . " LIKE " . $quotedlike;
                    $where[] = $field . " LIKE " . $quotedupper;
                    $where[] = $field . " LIKE " . $quotedlower;
                    $where[] = $field . " LIKE " . $quotedfirst;
                    $where[] = $field . " LIKE " . $quotedwords;
                    // remember what we selected before
                    $data['checked'][$field] = 1;
                }
                // reset the checkfield value
                $checkfield = NULL;
            }
            if (count($where) > 0) {
            // TODO: refresh fieldlist of datastore(s) before getting items
                $items =& $object->getItems(array('where' => join(' or ', $where)));

                if (isset($items) && count($items) > 0) {
                // TODO: combine retrieval of roles info above
                    foreach (array_keys($items) as $uid) {
                        if (isset($data['users'][$uid])) {
                            continue;
                        }
                        // Get user information
                        $data['users'][$uid] = xarModAPIFunc('roles',
                                                             'user',
                                                             'get',
                                                             array('uid' => $uid));
                    }
                }
            }
        }
    }

    // quote the search string
    $quotedlike = $dbconn->qstr('%'.$q.'%');

    $selection = " AND (";
    $selection .= "(xar_name LIKE " . $quotedlike . ")";
    $selection .= " OR (xar_uname LIKE " . $quotedlike . ")";

    if (xarModGetVar('roles', 'searchbyemail')) {
        $selection .= " OR (xar_email LIKE " . $quotedlike . ")";
    }

    $selection .= ")";

    $data['total'] = xarModAPIFunc('roles',
                                   'user',
                                   'countall',
                                   array('selection'   => $selection,
                                         'include_anonymous' => false,
                                         'include_myself' => false));

    if (!$data['total']){
        if (count($data['users']) == 0){
            $data['status'] = xarML('No Users Found Matching Search Criteria');
        }
        $data['total'] = count($data['users']);
        return $data;
    }

    $users = xarModAPIFunc('roles',
                           'user',
                           'getall',
                            array('startnum' => $startnum,
                                  'selection'   => $selection,
                                  'include_anonymous' => false,
                                  'include_myself' => false,
                                  'numitems' => xarModGetVar('roles',
                                                             'itemsperpage')));

    // combine search results with DD
    if (!empty($users) && count($data['users']) > 0) {
        foreach ($users as $user) {
            $uid = $user['uid'];
            if (isset($data['users'][$uid])) {
                continue;
            }
            $data['users'][$uid] = $user;
        }
    } else {
        $data['users'] = $users;
    }

    if (count($data['users']) == 0){
        $data['status'] = xarML('No Users Found Matching Search Criteria');
    }
    return $data;
}
?>
