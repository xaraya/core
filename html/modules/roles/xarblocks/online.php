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
function roles_onlineblock_init()
{
    // Security
    xarSecAddSchema('roles:Onlineblock:', 'Block title::');
}

/**
 * get information on block
 */
function roles_onlineblock_info()
{
    return array('text_type' => 'Online',
                 'module' => 'roles',
                 'text_type_long' => 'Display who is online');
}

/**
 * display block
 */
function roles_onlineblock_display($blockinfo)
{
    // Security check
    // Security check
    if (!xarSecurityCheck('ReadRole',1,'Onlineblock','$blockinfo[title]::')) return;

    // Get variables from content block
    $vars = unserialize($blockinfo['content']);

    // Database setup
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();
    $sessioninfotable = $xartable['session_info'];
    $activetime = time() - (xarConfigGetVar('Site.Session.Duration') * 60);
    $sql = "SELECT COUNT(1)
            FROM $sessioninfotable
            WHERE xar_lastused > $activetime AND xar_uid > 1
		    GROUP BY xar_uid
            ";
    $result = $dbconn->Execute($sql);

    if ($dbconn->ErrorNo() != 0) {
        return false;
    }
    $args['numroles'] = $result->RecordCount();
    $result->Close();

   $query2 = "SELECT count( 1 )
             FROM $sessioninfotable
              WHERE xar_lastused > $activetime AND xar_uid = '0'
			  GROUP BY xar_ipaddr
			 ";
   $result2 = $dbconn->Execute($query2);
   $args['numguests'] = $result2->RecordCount();
   $result2->Close();

       // Pluralise

   if ($args['numguests'] == 1) {
       $args['guests'] = xarML('guest');
   } else {
       $args['guests'] = xarML('guests');
   }

   if ($args['numroles'] == 1) {
       $args['roles'] = xarML('user');
   } else {
       $args['roles'] = xarML('roles');
   }


    // TODO Figure out the call for usergetvar.
 /*
    if (xarUserIsLoggedIn()) {
        $content .= '<br />'.xarML('Welcome Back').' <b> ' .xarUserGetVar('uid') . '</b>.<br />';

    }
 */
        //$content .= '<br />'.xarML('Welcome Back').';
        //<b>' .xarUserGetVar('uname') . '</b>.<br />';
        /*
        $column = &$pntable['priv_msgs_column'];
        $result2 = $dbconn->Execute("SELECT count(*) FROM $pntable[priv_msgs] WHERE $column[to_userid]=" . pnUserGetVar('uid'));
        list($numrow) = $result2->fields;
        if ($numrow == 0) {
            $content .= '<br /></span>';
        } else {
            $content .= "<br />"._YOUHAVE." <a class=\"pn-normal\" href=\"modules.php?op=modload&amp;name=Messages&amp;file=index\"><b>".pnVarPrepForDisplay($numrow)."</b></a> ";
            if ($numrow==1) {
               $content .= _PRIVATEMSG ;
           }
           elseif ($numrow>1) {
               $content .= _PRIVATEMSGS ;
           }
           $content .= "</span><br />";
        }
        */

    //} else {
    //    $content .= '<br />'.xarML('You are an anonymous user').'</span><br />';
    //}

    // Block formatting
    if (empty($blockinfo['title'])) {
        $blockinfo['title'] = xarML('Online');
    }

    $blockinfo['content'] = xarTplBlock('roles', 'online', $args);
    return $blockinfo;
}

/*
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

    //$numroles = 0;
    //$numguests = 0;

    $userlist = array();
    while (!$result->EOF) {
        list($uid, $num) = $result->fields;
        if ($uid == 0) {
            $numguests = $num;
        } else {
            $userlist[] = $uid;
            $numroles++;
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
        if ($numroles == 1) {
            $roles = xarML('member');
        } else {
            $roles = xarML('members');
        }

        $output->Text(xarML('There are currently'));//(_USERSCURRENTLY);
        $output->Text(" $numguests $guests ");
        $output->Text(xarML('and'));//(_USERSAND);
        $output->Text(" $numroles $roles ");
        $output->Text(xarML('online'));//(_USERSONLINE);
        $output->Linebreak();
    }

    if (!empty($vars['who'])) {
        $rolestable = $xartable['roles'];

        $userlist = join(',', $userlist);
        $sql = "SELECT xar_uname,
                       xar_uid
                FROM $rolestable
                WHERE xar_uid = 1
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
	    $output->URL(xarModURL('roles',
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
*/
// TODO - modify/update block settings


?>
