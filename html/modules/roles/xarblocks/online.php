<?php
/**
 * Online Block
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage roles module
 * @author Jim McDonald, Greg Allan, John Cox, Michael Makushev
 *
*/

/**
 * initialise block
 */
function roles_onlineblock_init()
{
    // No parameters accepted by this block.
    return array();
}

/**
 * get information on block
 */
function roles_onlineblock_info()
{
    return array(
        'text_type' => 'Online',
        'module' => 'roles',
        'text_type_long' => 'Display who is online'
    );
}

/**
 * Display func.
 * @param $blockinfo array containing title,content
 */
function roles_onlineblock_display($blockinfo)
{
    // Security check
    if (!xarSecurityCheck('ViewRoles',0,'Block',"online:$blockinfo[title]:All")) {return;}

    // Get variables from content block
    if (!is_array($blockinfo['content'])) {
        $vars = unserialize($blockinfo['content']);
    } else {
        $vars = $blockinfo['content'];
    }

    // Database setup
    // TODO: do we need this query? I'd have thought userapi/getallactive gives
    // us everything we need.
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $sessioninfotable = $xartable['session_info'];
    $activetime = time() - (xarConfigGetVar('Site.Session.Duration') * 60);
    $sql = "SELECT xar_uid
            FROM $sessioninfotable
            WHERE xar_lastused > ? AND xar_uid > 2
            GROUP BY xar_uid";
    $result = $dbconn->Execute($sql, array($activetime));

    if ($dbconn->ErrorNo() != 0) {return false;}

    $args['numusers'] = $result->RecordCount();

    $zz = xarModAPIFunc(
        'roles', 'user', 'getallactive',
        array(
            'order' => 'name',
            'startnum' => 0,
            'include_anonymous' => false,
            'include_myself' => true
        )
    );

    if (!empty($zz)) {
        foreach ($zz as $key => $aa) {
            $args['test1'][$key] = array(
                'name' => $aa['name'],
                'userurl' => xarModURL(
                    'roles', 'user', 'display',
                    array('uid' => $aa['uid'])
                ),
                'total' => '',
                'unread' => '',
                'messagesurl' => ''
            );

            if ($aa['name'] == xarUserGetVar('name')) {
                if (xarModIsAvailable('messages')) { 
                    $args['test1'][$key]['total'] = xarModAPIFunc(
                        'messages', 'user', 'count_total',
                        array('uid'=>$aa['uid'])
                    );

                    $args['test1'][$key]['unread'] = xarModAPIFunc(
                        'messages', 'user', 'count_unread',
                        array('uid'=>$aa['uid'])
                    );

                    $args['test1'][$key]['messagesurl'] =xarModURL(
                        'messages', 'user', 'display',
                        array('uid'=>$aa['uid'])
                    );
                }
            }
        }
    }

    $result->Close();

    $query2 = "SELECT count( 1 )
               FROM $sessioninfotable
               WHERE xar_lastused > ? AND xar_uid = 2
               GROUP BY xar_ipaddr";
    $result2 = $dbconn->Execute($query2, array($activetime));
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

    $uname = xarModGetVar('roles', 'lastuser');

    // Make sure we have a lastuser
    if (!empty($uname)) {
         $status = xarModAPIFunc(
            'roles', 'user', 'get',
            array('uname' => $uname)
         );

         // Check return
         if ($status) {$args['lastuser'] = $status;}
    }

    $args['blockid'] = $blockinfo['bid'];
    $blockinfo['content'] = $args;
    return $blockinfo;
}

?>