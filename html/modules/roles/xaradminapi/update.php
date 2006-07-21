<?php
/**
 * Update a role core info
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * Update a user's core info
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['uid'] user ID
 * @param $args['name'] user real name
 * @param $args['uname'] user nick name
 * @param $args['email'] user email address
 * @param $args['pass'] user password
 * TODO: move url to dynamic user data
 *       replace with status
 * @param $args['url'] user url
 */
function roles_adminapi_update($args)
{
    extract($args);

    // Argument check - make sure that all required arguments are present,
    // if not then set an appropriate error message and return
    if (!isset($uid))   throw new EmptyParameterException('uid');
    if (!isset($name))  throw new EmptyParameterException('name');
    if (!isset($uname)) throw new EmptyParameterException('uname');
    if (!isset($email)) throw new EmptyParameterException('email');
    if (!isset($state)) throw new EmptyParameterException('state');

    $item = xarModAPIFunc('roles',
            'user',
            'get',
            array('uid' => $uid));

    if ($item == false) throw new IDNotFoundException($uid);

    if (empty($valcode)) {
        $valcode = '';
    }
    if (empty($home)) {
        $home = '';
    }

    //FIXME: we need to standardize to 'itemtype' everywhere
    //$args['type'] = $itemtype;

    $role = new xarRole($args);
    $role->update();
    xarModSetUserVar('roles','userhome',$home,$uid);

    $item['module'] = 'roles';
    //$item['itemtype'] = $itemtype;
    $item['itemid'] = $uid;
    $item['name'] = $name;
    $item['home'] = $home;
    $item['uname'] = $uname;
    $item['email'] = $email;

    xarModCallHooks('item', 'update', $uid, $item);

    return true;
}

?>
