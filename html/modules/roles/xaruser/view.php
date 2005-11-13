<?php
/**
 * View users
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */
/**
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * view users
 */
function roles_user_view($args)
{
    extract($args);

    // Get parameters
    if(!xarVarFetch('startnum', 'int:1', $startnum, 1, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('phase', 'enum:active:viewall', $phase, 'active', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('name', 'notempty', $data['name'], '', XARVAR_NOT_REQUIRED)) {return;}

    if(!xarVarFetch('letter', 'str:1', $letter, NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('search', 'str:1:100', $search, NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('order', 'enum:name:uname:email:uid:state:date_reg', $order, 'name', XARVAR_NOT_REQUIRED)) {return;}

    // Bug 3338: disable 'selection' since it allows a user to manipulate the query directly
    //if(!xarVarFetch('selection', 'str', $selection, '', XARVAR_DONT_SET)) {return;}
    if (!isset($selection)) {$selection = '';}

    $data['items'] = array();

    // Specify some labels for display
    $data['pager'] = '';

    // Security Check
    if (!xarSecurityCheck('ReadRole')) return;

    // Need the database connection for quoting strings.
    $dbconn =& xarDBGetConn();

    // FIXME: SQL injection risk here - use bind variables.
    // NOTE: Cannot use bind variables here, until we know the knock-on
    // effect of changing the get*() API functions to accept bind variables.
    if ($letter) {
        if ($letter == 'Other') {
            // TODO: check for syntax in other databases or use a different matching method.
            $selection = " AND ("
                .$dbconn->substr."(".$dbconn->upperCase."(xar_name),1,1) < 'A' OR "
                .$dbconn->substr."(".$dbconn->upperCase."(xar_name),1,1) > 'Z')";
            // TODO: move these messages to the template (and shorten it a bit;-).
            $data['msg'] = xarML(
                'Members whose Display Name begins with character not listed in alphabet above (labeled as "Other")'
            );
        } else {
        // TODO: handle case-sensitive databases
            $selection = ' AND xar_name LIKE ' . $dbconn->qstr($letter.'%');
            if(strtolower($phase) == 'active') {
                $data['msg'] = xarML('Members Online whose Display Name begins with "#(1)"', $letter);
            } else {
                $data['msg'] = xarML('Members whose Display Name begins with "#(1)"', $letter);
            }
        }
    } elseif ($search) {
        // Quote the search string
        $qsearch = $dbconn->qstr('%'.$search.'%');

        $selection = ' AND (';
        $selection .= '(xar_name LIKE ' . $qsearch . ')';
        $selection .= ' OR (xar_uname LIKE ' . $qsearch . ')';
        if (xarModGetVar('roles', 'searchbyemail')) {
            $selection .= ' OR (xar_email LIKE ' . $qsearch . ')';
            $data['msg'] = xarML('Members whose Display Name or User Name or Email Address contains "#(1)"', $search);
        } else {
            $data['msg'] = xarML('Members whose Display Name or User Name "#(1)"', $search);
        }
        $selection .= ")";
    } else {
        if(strtolower($phase) == 'active') {
            $data['msg'] = xarML("All members online");
        } else {
            $data['msg'] = xarML("All members");
        }
    }

    $data['order'] = $order;
    $data['letter'] = $letter;
    $data['search'] = $search;
    $data['searchlabel'] = xarML('Go');

    $data['alphabet'] = array(
        'A', 'B', 'C', 'D', 'E', 'F',
        'G', 'H', 'I', 'J', 'K', 'L',
        'M', 'N', 'O', 'P', 'Q', 'R',
        'S', 'T', 'U', 'V', 'W', 'X',
        'Y', 'Z'
    );

    switch(strtolower($phase)) {
        case 'active':
            $data['phase'] = 'active';
            $filter = time() - (xarConfigGetVar('Site.Session.Duration') * 60);
            $data['title'] = xarML('Online Members');

            $data['total'] = xarModAPIFunc(
                'roles', 'user', 'countallactive',
                array(
                    'filter'   => $filter,
                    'selection'   => $selection,
                    'include_anonymous' => false,
                    'include_myself' => false
                )
            );
            xarTplSetPageTitle(xarVarPrepForDisplay(xarML('Active Members')));

            if (!$data['total']) {
                $data['message'] = xarML('There are no online members selected');
                $data['total'] = 0;
                return $data;
            }

            // Now get the actual records to be displayed
            $items = xarModAPIFunc(
                'roles', 'user', 'getallactive',
                array(
                    'startnum' => $startnum,
                    'filter'   => $filter,
                    'order'   => $order,
                    'selection'   => $selection,
                    'include_anonymous' => false,
                    'include_myself' => false,
                    'numitems' => xarModGetVar('roles', 'rolesperpage')
                )
            );
            break;

        case 'viewall':
            $data['phase'] = 'viewall';
            $data['title'] = xarML('All Members');

            $data['total'] = xarModAPIFunc(
                'roles', 'user', 'countall',
                array(
                    'selection' => $selection,
                    'include_anonymous' => false,
                    'include_myself' => false
                )
            );

            xarTplSetPageTitle(xarVarPrepForDisplay(xarML('All Members')));

            if (!$data['total']) {
                $data['message'] = xarML('There are no members selected');
                $data['total'] = 0;
                return $data;
            }

            // Now get the actual records to be displayed
            $items = xarModAPIFunc(
                'roles', 'user', 'getall',
                array(
                    'startnum' => $startnum,
                    'order' => $order,
                    'selection' => $selection,
                    'include_anonymous' => false,
                    'include_myself' => false,
                    'numitems' => xarModGetVar('roles', 'rolesperpage')
                )
            );
            break;
    }

    // keep track of the selected uid's
    $data['uidlist'] = array();

    // Check individual privileges for Edit / Delete
    for ($i = 0, $max = count($items); $i < $max; $i++) {
        $item = $items[$i];
        $data['uidlist'][] = $item['uid'];

        // Grab the list of groups this role belongs to
        $groups = xarModAPIFunc('roles', 'user', 'getancestors', array('uid' => $item['uid']));
        foreach ($groups as $group) {
            $items[$i]['groups'][$group['uid']] = $group['name'];
        }

        // Change email to a human readible entry.  Anti-Spam protection.
        if (xarUserIsLoggedIn()) {
            $items[$i]['emailurl'] = xarModURL(
                'roles', 'user', 'email',
                array('uid' => $item['uid'])
            );
        } else {
            $items[$i]['emailurl'] = '';
        }

        if (empty($items[$i]['ipaddr'])) {
            $items[$i]['ipaddr'] = '';
        }
        $items[$i]['emailicon'] = xarTplGetImage('emailicon.gif');
        $items[$i]['infoicon'] = xarTplGetImage('infoicon.gif');
    }
    $data['pmicon'] = '';
    // Add the array of items to the template variables
    $data['items'] = $items;

    $numitems = xarModGetVar('roles', 'rolesperpage');
    $pagerfilter['phase'] = $phase;
    $pagerfilter['order'] = $order;
    $pagerfilter['letter'] = $letter;
    $pagerfilter['search'] = $search;
    $pagerfilter['startnum'] = '%%';

    $data['pager'] = xarTplGetPager(
        $startnum,
        $data['total'],
        xarModURL('roles', 'user', 'view', $pagerfilter),
        $numitems
    );
    return $data;
}

?>
