<?php 
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Jim McDonald
// Purpose of file: display who/how many people are online
// ----------------------------------------------------------------------

/**
 * initialise block
 */
function users_onlineblock_init()
{
    // Security
    xarSecAddSchema('users:Onlineblock:', 'Block title::');
}

/**
 * get information on block
 */
function users_onlineblock_info()
{
    return array('text_type' => 'Online',
                 'module' => 'users',
                 'text_type_long' => 'Display who is online');
}

/**
 * display block
 */
function users_onlineblock_display($blockinfo)
{
    // Security check
    if (!xarSecAuthAction(0,
                         'users:Onlineblock:',
                         "$blockinfo[title]::",
                         ACCESS_READ)) {
        return;
    }

    // Get variables from content block
    $vars = unserialize($blockinfo['content']);

    // Defaults
    if (empty($vars['howmany'])) {
        $vars['howmany'] = 1;
    }
    if (empty($vars['who'])) {
        $vars['who'] = 1;
    }
    if (empty($vars['maxwho'])) {
        $vars['maxwho'] = 10;
    }

    if ((empty($vars['howmany'])) &&
        (empty($vars['who']))) {
        // Nothing to display
        return;
    }

    $output = new pnHTML();

    // Database setup
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $sessioninfotable = $xartable['session_info'];

    $activetime = time() - (xarConfigGetVar('secinactivemins') * 60);

    // TODO - see if this can be done in a better way
    $query = "SELECT xar_uid,
                   COUNT(1)
            FROM $sessioninfotable
            WHERE xar_lastused > $activetime
            GROUP BY xar_uid";
    $result = $dbconn->Execute($query);

    if ($dbconn->ErrorNo() != 0) {
        // Error getting information - return
        // TODO - handle this better?
        return;
    }

    $numusers = 0;
    $numguests = 0;

    $userlist = array();
    while (!$result->EOF) {
        list($uid, $num) = $result->fields;
        if ($uid == 0) {
            $numguests = $num;
        } else {
            $userlist[] = $uid;
            $numusers++;
        }
        
        $result->MoveNext();
    }
    $result->Close();

    if (!empty($vars['howmany'])) {
        // Pluralise
        if ($numguests == 1) {
            $guests = xarML('guest');
        } else {
            $guests = xarML('guests');
        }
        if ($numusers == 1) {
            $users = xarML('member');
        } else {
            $users = xarML('members');
        }

        $output->Text(xarML('guest'));//(_USERSCURRENTLY);
        $output->Text(" $numguests $guests ");
        $output->Text(xarML('guest'));//(_USERSAND);
        $output->Text(" $numusers $users ");
        $output->Text(xarML('guest'));//(_USERSONLINE);
        $output->Linebreak();
    }

    if (!empty($vars['who'])) {
        $userstable = $xartable['users'];

        $userlist = join(',', $userlist);
        $sql = "SELECT xar_uname,
                       xar_uid
                FROM $userstable
                WHERE xar_uid in (" . xarVarPrepForStore($userlist) . ")
                ORDER BY xar_uname";
        $result = $dbconn->Execute($sql);

        if ($dbconn->ErrorNo()) {//echo $sql.$dbconn->ErrorMsg();exit;
            // Error getting information - return
            // TODO - handle this better?
            return;
        }

        if (!$result->EOF) {
            if (!empty($vars['howmany'])) {
                $output->Linebreak();
            }
            $output->Text(xarML('guest'));//(_USERSLOGGEDIN);
            $output->Linebreak();
        }

        $numdisplayed=0;
        for (; !$result->EOF && $numdisplayed<$vars['maxwho']; $result->MoveNext()) {
            list($uname, $uid) = $result->fields;
	    $output->URL(xarModURL('users',
				  'user',
				  'display',
				  array('uid' => $uid)),
			 $uname);
            $output->Linebreak();
        }
        $result->Close();


    }


    // Block formatting
    if (empty($blockinfo['title'])) {
        $blockinfo['title'] = pnML('Online');
    }

    $blockinfo['content'] = $output->GetOutput();

    return $blockinfo;
}

// TODO - modify/update block settings

?>
