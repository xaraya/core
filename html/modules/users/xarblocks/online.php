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
    pnSecAddSchema('users:Onlineblock:', 'Block title::');
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
    if (!pnSecAuthAction(0,
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
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $sessioninfotable = $pntable['session_info'];

    $activetime = time() - (pnConfigGetVar('secinactivemins') * 60);

    // TODO - see if this can be done in a better way
    $query = "SELECT pn_uid,
                   COUNT(1)
            FROM $sessioninfotable
            WHERE pn_lastused > $activetime
            GROUP BY pn_uid";
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
            $guests = _USERSGUEST;
        } else {
            $guests = _USERSGUESTS;
        }
        if ($numusers == 1) {
            $users = _USERSMEMBER;
        } else {
            $users = _USERSMEMBERS;
        }

        $output->Text(_USERSCURRENTLY);
        $output->Text(" $numguests $guests ");
        $output->Text(_USERSAND);
        $output->Text(" $numusers $users ");
        $output->Text(_USERSONLINE);
        $output->Linebreak();
    }

    if (!empty($vars['who'])) {
        $userstable = $pntable['users'];

        $userlist = join(',', $userlist);
        $sql = "SELECT pn_uname,
                       pn_uid
                FROM $userstable
                WHERE pn_uid in (" . pnVarPrepForStore($userlist) . ")
                ORDER BY pn_uname";
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
            $output->Text(_USERSLOGGEDIN);
            $output->Linebreak();
        }

        $numdisplayed=0;
        for (; !$result->EOF && $numdisplayed<$vars['maxwho']; $result->MoveNext()) {
            list($uname, $uid) = $result->fields;
	    $output->URL(pnModURL('users',
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
        $blockinfo['title'] = _USERSWHOSONLINE;
    }

    $blockinfo['content'] = $output->GetOutput();

    return $blockinfo;
}

// TODO - modify/update block settings

?>