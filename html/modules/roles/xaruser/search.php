<?php

function roles_user_search()
{

    if(!xarVarFetch('startnum', 'isset', $startnum,  NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('email',    'isset', $email,     NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('uname',    'isset', $uname,     NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('name',     'isset', $name,      NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('q',        'isset', $q,         NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('bool',     'isset', $bool,      NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('sort',     'isset', $sort,      NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('author',   'isset', $author,    NULL, XARVAR_NOT_REQUIRED)) {return;}


    $data = array();
    $data['users'] = array();

    // show the search form
    if (!isset($q)) {
        if (xarModIsHooked('dynamicdata','roles')) {
            // get the Dynamic Object defined for this module
            $object =& xarModAPIFunc('dynamicdata','user','getobject',
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

    // TODO: support wild cards / boolean / quotes / ... (cfr. articles) ?

    // TODO: provide roles API to search on several criteria ?

    // remember what we selected before
    $data['checked'] = array();

    if (isset($name)){
        $data['checked']['name'] = 1;
        // Get user information
        $user = xarModAPIFunc('roles',
                              'user',
                              'get',
                              array('name' => $q));
        if (!empty($user)) {
            $data['users'][$user['uid']] = $user;
        }
    }

    if (isset($uname)){
        $data['checked']['uname'] = 1;
        // Get user information
        $user = xarModAPIFunc('roles',
                              'user',
                              'get',
                              array('uname' => $q));
        if (!empty($user)) {
            $data['users'][$user['uid']] = $user;
        }
    }

    if (isset($email)){
        $data['checked']['email'] = 1;
        // Get user information
        $user = xarModAPIFunc('roles',
                              'user',
                              'get',
                              array('email' => $q));
        if (!empty($user)) {
            $data['users'][$user['uid']] = $user;
        }
    }

    if (xarModIsHooked('dynamicdata','roles')) {
        // make sure the DD classes are loaded
        if (!xarModAPILoad('dynamicdata','user')) return $data;

        // get a new object list for roles
        $object = new Dynamic_Object_List(array('moduleid'  => xarModGetIDFromName('roles')));

        if (isset($object) && !empty($object->objectid)) {
            // save the properties for the search form
            $data['properties'] =& $object->getProperties();

            // run the search query
            $q = xarVarPrepForStore($q);
            $where = array();
            // see which properties we're supposed to search in
            foreach (array_keys($object->properties) as $field) {
                $checkfield = xarVarCleanFromInput($field);
                if (!empty($checkfield)) {
                    $where[] = $field . " eq '$q'";
                    // remember what we selected before
                    $data['checked'][$field] = 1;
                }
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

    if (count($data['users']) == 0){
        $data['status'] = xarML('No Users Found Matching Search');
    }

    return $data;
}

?>