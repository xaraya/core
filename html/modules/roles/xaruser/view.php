<?php

/**
 * view users
 */
function roles_user_view()
{
    // Get parameters
    if(!xarVarFetch('startnum', 'isset',    $startnum, NULL,     XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('phase',    'notempty', $phase,    'active', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('name',    'notempty', $data['name'],'', XARVAR_NOT_REQUIRED)) {return;}
    //This $filter variables isnt being used for anything...
    //It is set later on.
    if(!xarVarFetch('filter',   'str',   $filter,   NULL,     XARVAR_DONT_SET)) {return;}

    if(!xarVarFetch('letter',   'str',   $letter,   NULL,     XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('search',   'str',   $search,   NULL,     XARVAR_DONT_SET)) {return;}

    $data['items'] = array();

    // Specify some labels for display
    $data['pager'] = '';

    $perpage = xarModGetVar('roles','rolesperpage');
    if (empty($perpage)) {
        $perpage = 20;
        xarModSetVar('roles','rolesperpage',$perpage);
    }

// Security Check
    if(!xarSecurityCheck('ReadRole')) return;

    if ($letter) $selection = " AND xar_name LIKE '" . $letter . "%'";
    elseif ($search) {
        $selection = " AND (";
        $selection .= "(xar_name LIKE '%" . $search . "%')";
        $selection .= " OR (xar_uname LIKE '%" . $search . "%')";
        $selection .= " OR (xar_email LIKE '%" . $search . "%')";
        $selection .= ")";
    }
    else {
        $selection = "";
    }

    $data['searchlabel'] = xarML('Go');
    $data['alphabet'] = array ("A","B","C","D","E","F","G","H","I","J","K","L","M",
                            "N","O","P","Q","R","S","T","U","V","W","X","Y","Z");

    switch(strtolower($phase)) {

        case 'active':

            $data['phase'] = 'active';
            $filter = time() - (xarConfigGetVar('Site.Session.Duration') * 60);

            $data['title'] = xarML('Online Members');
            // The user API function is called.
            $items = xarModAPIFunc('roles',
                                   'user',
                                   'getallactive',
                                    array('startnum' => $startnum,
                                          'filter'   => $filter,
                                          'selection'   => $selection,
                                          'include_anonymous' => false,
                                          'include_myself' => false,
                                          'numitems' => xarModGetVar('roles',
                                                                     'itemsperpage')));

            xarTplSetPageTitle(xarVarPrepForDisplay(xarML('Active Users')));

            if ($items == false){
                $data['message'] = xarML('There are no registered users online');
                return $data;
            }

            break;

        case 'viewall':
            $data['phase'] = 'viewall';
            $data['title'] = xarML('All Members');
            // The user API function is called.
            $items = xarModAPIFunc('roles',
                                   'user',
                                   'getall',
                                    array('startnum' => $startnum,
                                          'state'   => 3,
                                          'selection'   => $selection,
                                          'include_anonymous' => false,
                                          'include_myself' => false,
                                          'numitems' => xarModGetVar('roles',
                                                                     'itemsperpage')));

            if ($items == false) return;

            xarTplSetPageTitle(xarVarPrepForDisplay(xarML('All Users')));

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

        $items[$i]['emailicon'] = '<img src="' . xarTplGetImage('emailicon.gif') . '" />';

        $items[$i]['infoicon'] = '<img src="' . xarTplGetImage('infoicon.gif') . '" />';

    }

    $data['pmicon'] = '';
    // Add the array of items to the template variables
    $data['items'] = $items;

    // TODO : add a pager (once it exists in BL)
    $data['pager'] = '';

    // meanwhile, let's do at least *something* here :)
    if (empty($startnum) || $startnum < 2) {
    //    $data['pager'] .= xarML('previous');
    } else {
        $data['pager'] .= '<a href="' . xarModURL('roles','user','view',array('phase' => $phase, 'startnum' => $startnum - $perpage)) . '">';
        $data['pager'] .= '&lt;&lt; ' . xarML('previous') . '</a>';
    }
    $data['pager'] .= '&nbsp;&nbsp;&nbsp;';
    // poor man's counter
// TODO: add countitems depending on selected 'phase'
    if (count($data['uidlist']) < $perpage) {
    //    $data['pager'] .= xarML('next');
    } else {
        if (empty($startnum)) {
            $startnum = 1;
        }
        $data['pager'] .= '<a href="' . xarModURL('roles','user','view',array('phase' => $phase, 'startnum' => $startnum + $perpage)) . '">';
        $data['pager'] .= xarML('next') . ' &gt;&gt;</a>';
    }

    return $data;
}

?>
