<?php
/**
 * File: $Id: s.online.php 1.25 03/06/10 20:10:43+02:00 marc@marclaptop. $
 *
 * Online Block
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage roles module
 * @author Jim McDonald, Greg Allan, John Cox
*/

/**
 * initialise block
 */
function roles_onlineblock_init()
{
    return true;
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
    if (!xarSecurityCheck('ViewRoles',0,'Block',"All:" . $blockinfo['title'] . ":All", 'All')) return;

    // Get variables from content block
    $vars = unserialize($blockinfo['content']);

    // Database setup
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();
    $sessioninfotable = $xartable['session_info'];
    $activetime = time() - (xarConfigGetVar('Site.Session.Duration') * 60);
    $sql = "SELECT COUNT(1)
            FROM $sessioninfotable
            WHERE xar_lastused > $activetime AND xar_uid > 2
            GROUP BY xar_uid
            ";
    $result = $dbconn->Execute($sql);

    if ($dbconn->ErrorNo() != 0) {
        return false;
    }
    $args['numusers'] = $result->RecordCount();
    $result->Close();

   $query2 = "SELECT count( 1 )
             FROM $sessioninfotable
              WHERE xar_lastused > $activetime AND xar_uid = '2'
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

   if ($args['numusers'] == 1) {
       $args['users'] = xarML('user');
   } else {
       $args['users'] = xarML('users');
   }
   $args['blockid'] = $blockinfo['bid'];
    // Block formatting
    if (empty($blockinfo['title'])) {
        $blockinfo['title'] = xarML('Online');
    }

    $blockinfo['content'] = xarTplBlock('roles', 'online', $args);
    return $blockinfo;
}

?>
