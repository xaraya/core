<?php
/**
 * @package modules\roles
 * @subpackage roles
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/27.html
 */

/**
 * Search
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @return array data for the template display
 */
function roles_user_search()
{
    if (!xarVarFetch('startnum', 'isset', $startnum,  NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('email',    'isset', $email,     NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('uname',    'isset', $uname,     NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('name',     'isset', $name,      NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('q',        'isset', $q,         NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('bool',     'isset', $bool,      NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('sort',     'isset', $sort,      NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('author',   'isset', $author,    NULL, XARVAR_DONT_SET)) {return;}
    $data = array();
    $data['users'] = array();
    // show the search form
    if (!isset($q)) {
        if (xarModIsHooked('dynamicdata','roles')) {
            // get the DataObject defined for this module
            $object = xarMod::apiFunc('dynamicdata','user','getobject',
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
    $dbconn = xarDB::getConn();

    // TODO: support wild cards / boolean / quotes / ... (cfr. articles) ?

    // remember what we selected before
    $data['checked'] = array();

    if (xarModIsHooked('dynamicdata','roles')) {
        // make sure the DD classes are loaded
        if (!xarModAPILoad('dynamicdata','user')) return $data;

        // get a new object list for roles
        $object = new DataObjectList(array('moduleid'  => xarMod::getRegID('roles')));

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
                    foreach (array_keys($items) as $id) {
                        if (isset($data['users'][$id])) {
                            continue;
                        }
                        // Get user information
                        $data['users'][$id] = xarMod::apiFunc('roles', 'user', 'get',
                                                       array('id' => $id));
                    }
                }
            }
        }
    }

    // quote the search string
    $quotedlike = $dbconn->qstr('%'.$q.'%');

    $selection = " AND (";
    $selection .= "(name LIKE " . $quotedlike . ")";
    $selection .= " OR (uname LIKE " . $quotedlike . ")";

    if (xarModVars::get('roles', 'searchbyemail')) {
        $selection .= " OR (email LIKE " . $quotedlike . ")";
    }

    $selection .= ")";

    $data['total'] = xarMod::apiFunc('roles', 'user', 'countall',
                             array('selection'         => $selection,
                                   'include_anonymous' => false));

    if (!$data['total']){
        if (count($data['users']) == 0){
            $data['status'] = xarML('No Users Found Matching Search Criteria');
        }
        $data['total'] = count($data['users']);
        return $data;
    }

    $users = xarMod::apiFunc('roles',
                           'user',
                           'getall',
                            array('startnum'          => $startnum,
                                  'selection'         => $selection,
                                  'include_anonymous' => false,
                                  'numitems'          => (int)xarModVars::get('roles', 'items_per_page')));

    // combine search results with DD
    if (!empty($users) && count($data['users']) > 0) {
        foreach ($users as $user) {
            $id = $user['id'];
            if (isset($data['users'][$id])) {
                continue;
            }
            $data['users'][$id] = $user;
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
