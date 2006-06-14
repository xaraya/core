<?php
/**
 * Redirect for validating users
 *
 * @package server
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @author John Cox
 * @TODO jojodee - rethink dependencies between roles, authentication(authsystem) and 
 *                 registration in relation to validation
*/

/**
 *  initialize the Xaraya core
 */
include 'includes/xarCore.php';
xarCoreInit(XARCORE_SYSTEM_ALL);

if (!xarVarFetch('v', 'str:1', $v)) return;
if (!xarVarFetch('u', 'str:1', $u)) return;

$user = xarModAPIFunc('roles','user','get', array('uid' => $u));

xarResponseRedirect(xarModURL('roles', 'user','getvalidation',
                              array('stage'   => 'getvalidate',
                                    'valcode' => $v,
                                    'uname'   => $user['uname'],
                                    'phase'   => 'getvalidate')));

?>
