<?php
/**
 * Redirect for validating users
 *
 * @package entrypoint
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @author John Cox
 * @TODO jojodee - rethink dependencies between roles, authentication(authsystem) and
 *                 registration in relation to validation
 */

/**
 *  initialize the Xaraya core
 */
set_include_path(dirname(dirname(__FILE__)) . PATH_SEPARATOR . get_include_path());
include_once('lib/bootstrap.php');
sys::import('xaraya.core');
xarCoreInit();

if (!xarVarFetch('v', 'str:1', $v)) return;
if (!xarVarFetch('u', 'str:1', $u)) return;

$user = xarModAPIFunc('roles','user','get', array('uid' => $u));

xarResponseRedirect(xarModURL('roles', 'user','getvalidation',
                              array('stage'   => 'getvalidate',
                                    'valcode' => $v,
                                    'uname'   => $user['uname'],
                                    'phase'   => 'getvalidate')));

?>
