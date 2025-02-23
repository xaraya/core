<?php

/**
 * @package modules\roles
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Roles\UserGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Roles\UserGui;
use Xaraya\Modules\Roles\UserApi;
use DataObject;
use DataObjectDescriptor;
use DataObjectList;
use xarDB;
use xarHooks;
use xarMod;
use xarModVars;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles user search function
 * @extends MethodClass<UserGui>
 */
class SearchMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Search
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @return array|void data for the template display
     * @see UserGui::search()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        $this->var()->check('startnum', $startnum);
        $this->var()->check('email', $email);
        $this->var()->check('uname', $uname);
        $this->var()->check('name', $name);
        $this->var()->check('q', $q);
        $this->var()->check('bool', $bool);
        $this->var()->check('sort', $sort);
        $this->var()->check('author', $author);
        $data = [];
        $data['users'] = [];
        // show the search form
        if (!isset($q)) {
            if (xarHooks::isAttached('dynamicdata', 'roles')) {
                // get the DataObject defined for this module
                /** @var DataObject $object */
                $object = $this->data()->getObject(['module' => 'roles']);
                if (isset($object) && !empty($object->objectid)) {
                    // get the Dynamic Properties of this object
                    $data['properties'] = & $object->getProperties();
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
        $data['checked'] = [];

        if (xarHooks::isAttached('dynamicdata', 'roles')) {
            // make sure the DD classes are loaded
            if (!xarMod::apiLoad('dynamicdata', 'user')) {
                return $data;
            }

            // @todo load the right object for roles here
            // get a new object list for roles
            $descriptor = new DataObjectDescriptor(['moduleid'  => xarMod::getRegID('roles')]);
            $object = new DataObjectList($descriptor);

            if (isset($object) && !empty($object->objectid)) {
                // save the properties for the search form
                $data['properties'] = & $object->getProperties();

                // quote the search string (in different variations here)
                $quotedlike = $dbconn->qstr('%' . $q . '%');
                $quotedupper = $dbconn->qstr('%' . strtoupper($q) . '%');
                $quotedlower = $dbconn->qstr('%' . strtolower($q) . '%');
                $quotedfirst = $dbconn->qstr('%' . ucfirst($q) . '%');
                $quotedwords = $dbconn->qstr('%' . ucwords($q) . '%');

                // run the search query
                $where = [];
                // see which properties we're supposed to search in
                foreach (array_keys($object->properties) as $field) {
                    $this->var()->find($field, $checkfield, 'checkbox');
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
                    $checkfield = null;
                }
                if (count($where) > 0) {
                    // TODO: refresh fieldlist of datastore(s) before getting items
                    $items = & $object->getItems(['where' => join(' or ', $where)]);

                    if (isset($items) && count($items) > 0) {
                        // TODO: combine retrieval of roles info above
                        foreach (array_keys($items) as $id) {
                            if (isset($data['users'][$id])) {
                                continue;
                            }
                            // Get user information
                            $data['users'][$id] = $userapi->get(['id' => $id]);
                        }
                    }
                }
            }
        }

        // quote the search string
        $quotedlike = $dbconn->qstr('%' . $q . '%');

        $selection = " AND (";
        $selection .= "(name LIKE " . $quotedlike . ")";
        $selection .= " OR (uname LIKE " . $quotedlike . ")";

        if (xarModVars::get('roles', 'searchbyemail')) {
            $selection .= " OR (email LIKE " . $quotedlike . ")";
        }

        $selection .= ")";

        $data['total'] = $userapi->countall(['selection'         => $selection,
            'include_anonymous' => false]);

        if (!$data['total']) {
            if (count($data['users']) == 0) {
                $data['status'] = xarML('No Users Found Matching Search Criteria');
            }
            $data['total'] = count($data['users']);
            return $data;
        }

        $users = $userapi->getall(['startnum'          => $startnum,
            'selection'         => $selection,
            'include_anonymous' => false,
            'numitems'          => (int) xarModVars::get('roles', 'items_per_page')]);

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

        if (count($data['users']) == 0) {
            $data['status'] = xarML('No Users Found Matching Search Criteria');
        }
        return $data;
    }
}
