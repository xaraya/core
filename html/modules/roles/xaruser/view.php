<?php

/**
 * view users
 */
function roles_user_view()
{
    // Get parameters
    if(!xarVarFetch('startnum', 'isset',    $startnum, 1,     XARVAR_NOT_REQUIRED, 1)) {return;}
    if(!xarVarFetch('phase',    'notempty', $phase,    'active', XARVAR_NOT_REQUIRED, 1)) {return;}
    if(!xarVarFetch('name',    'notempty', $data['name'],'', XARVAR_NOT_REQUIRED, 1)) {return;}
    //This $filter variable isnt being used for anything...
    //It is set later on.
    if(!xarVarFetch('filter',   'str',   $filter,   NULL,     XARVAR_DONT_SET, 1)) {return;}

    if(!xarVarFetch('letter',   'str',   $letter,   NULL,     XARVAR_NOT_REQUIRED, 1)) {return;}
    if(!xarVarFetch('search',   'str',   $search,   NULL,     XARVAR_NOT_REQUIRED, 1)) {return;}
    if(!xarVarFetch('order',    'str',   $order,    "name",   XARVAR_NOT_REQUIRED, 1)) {return;}
    if(!xarVarFetch('selection','str',   $selection,  "",     XARVAR_DONT_SET, 1)) {return;}

    $data['items'] = array();

    // Specify some labels for display
    $data['pager'] = '';

    $perpage = xarModGetVar('roles','itemsperpage');

// Security Check
    if(!xarSecurityCheck('ReadRole')) return;

    if ($letter) {
        $selection = " AND xar_name LIKE '" . $letter . "%'";
        $data['msg'] = xarML("Members starting with '" . $letter . "'");
    }
    elseif ($search) {
        $selection = " AND (";
        $selection .= "(xar_name LIKE '%" . $search . "%')";
        $selection .= " OR (xar_uname LIKE '%" . $search . "%')";
        $selection .= " OR (xar_email LIKE '%" . $search . "%')";
        $selection .= ")";
        $data['msg'] = xarML("Members containing '" . $search . "'");
    }
    else {
        $data['msg'] = xarML("All members");
    }

    $data['order'] = $order;
    $data['letter'] = $letter;
    $data['search'] = $search;
    $data['searchlabel'] = xarML('Go');
    $data['alphabet'] = array ("A","B","C","D","E","F","G","H","I","J","K","L","M",
                            "N","O","P","Q","R","S","T","U","V","W","X","Y","Z");
    $filter['startnum'] = $startnum;

    switch(strtolower($phase)) {

        case 'active':

            $data['phase'] = 'active';
            $filter = time() - (xarConfigGetVar('Site.Session.Duration') * 60);

            $data['title'] = xarML('Online Members');
            // The user API function is called. First pass to get the total number of records
            // for the pager. Not very efficient, but there you are.
            $items = xarModAPIFunc('roles',
                                   'user',
                                   'getallactive',
                                    array('startat' => 0,
                                          'filter'   => $filter,
                                          'order'   => $order,
                                          'selection'   => $selection,
                                          'include_anonymous' => false,
                                          'include_myself' => false,
                                          'numitems' => xarModGetVar('roles',
                                                                     'rolesperpage')));

            xarTplSetPageTitle(xarVarPrepForDisplay(xarML('Active Users')));

            if (!$items){
                $data['message'] = xarML('There are no active users selected');
                $data['total'] = 0;
                return $data;
            }
            $data['total'] = count($items);

            // Now get the actual records to be displayed
            $items = xarModAPIFunc('roles',
                                   'user',
                                   'getallactive',
                                    array('startat' => $startnum,
                                          'filter'   => $filter,
                                          'order'   => $order,
                                          'selection'   => $selection,
                                          'include_anonymous' => false,
                                          'include_myself' => false,
                                          'numitems' => xarModGetVar('roles',
                                                                     'rolesperpage')));
            break;

        case 'viewall':
            $data['phase'] = 'viewall';
            $data['title'] = xarML('All Members');

            // The user API function is called. First pass to get the total number of records
            // for the pager. Not very efficient, but there you are.
            $items = xarModAPIFunc('roles',
                                   'user',
                                   'getall',
                                    array('startat' => 0,
                                          'order'   => $order,
                                          'selection'   => $selection,
                                          'include_anonymous' => false,
                                          'include_myself' => false,
                                          'numitems' => xarModGetVar('roles',
                                                                     'rolesperpage')));

            if (!$items){
                $data['message'] = xarML('There are no users selected');
                $data['total'] = 0;
                return $data;
            }

            xarTplSetPageTitle(xarVarPrepForDisplay(xarML('All Users')));

            $data['total'] = count($items);

            // Now get the actual records to be displayed
            $items = xarModAPIFunc('roles',
                                   'user',
                                   'getall',
                                    array('startat' => $startnum,
                                          'order'   => $order,
                                          'selection'   => $selection,
                                          'include_anonymous' => false,
                                          'include_myself' => false,
                                          'numitems' => xarModGetVar('roles',
                                                                     'rolesperpage')));
            break;
    }

    // keep track of the selected uid's
    $data['uidlist'] = array();

    // Check individual permissions for Edit / Delete
    for ($i = 0; $i < count($items); $i++) {
        $item = $items[$i];
        $data['uidlist'][] = $item['uid'];

        switch(strtolower($phase)) {

            case 'active':
                $getuser = xarModAPIFunc('roles',
                                         'user',
                                         'get',
                                          array('uid' => $items[$i]['uid']));


                $items[$i]['name'] = $getuser['name'];
                $items[$i]['email'] = $getuser['email'];
                break;
        }

        // Change email to a human readible entry.  Anti-Spam protection.

        if (xarUserIsLoggedIn()) {
            $items[$i]['emailurl'] = xarModURL('roles',
                                               'user',
                                               'email',
                                                array('uid' => $item['uid']));

        } else {
            $items[$i]['emailurl'] = '';
        }

        if (empty($items[$i]['ipaddr'])){
            $items[$i]['ipaddr'] = '';
        }

        $items[$i]['emailicon'] = '<img src="' . xarTplGetImage('emailicon.gif') . '" alt="Email" title="Email" />';

        $items[$i]['infoicon'] = '<img src="' . xarTplGetImage('infoicon.gif') . '" />';

    }

    $data['pmicon'] = '';
    // Add the array of items to the template variables
    $data['items'] = $items;

    $numitems = xarModGetVar('roles','rolesperpage');
    $pagerfilter['phase'] = $phase;
    $pagerfilter['order'] = $order;
    $pagerfilter['letter'] = $letter;
    $pagerfilter['search'] = $search;
    $pagerfilter['startnum'] = '%%';
    $data['pager'] = xarTplGetPager($startnum,
                            $data['total'],
                            xarModURL('roles', 'user', 'view',
                                      $pagerfilter),
                            $numitems);
    return $data;
}

?>
