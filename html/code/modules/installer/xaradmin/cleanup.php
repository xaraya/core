<?php
/**
 * Installer
 *
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/42.html
 */

/* Do not allow this script to run if the install script has been removed.
 * This assumes the install.php and index.php are in the same directory.
 * @author Paul Rosania
 * @author Marcel van der Boom <marcel@hsdev.com>
 */

function installer_admin_cleanup()
{
    if (!file_exists('install.php')) { throw new Exception('Already installed');}
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);
    xarTplSetThemeName('installer');

    xarVarFetch('remove', 'checkbox', $remove, false, XARVAR_NOT_REQUIRED);
    xarVarFetch('rename', 'checkbox', $rename, false, XARVAR_NOT_REQUIRED);
    xarVarFetch('newname', 'str', $newname, '', XARVAR_NOT_REQUIRED);

    if ($remove) {
        try {
            unlink('install.php');
        } catch (Exception $e) {
            return xarTplModule('installer','user','errors',array('layout' => 'no_permission_delete', 'filename' => 'install.php'));
        }
    } elseif ($rename) {
        if (empty($newname)) {
            try {
                unlink('install.php');
            } catch (Exception $e) {
                return xarTplModule('installer','user','errors',array('layout' => 'no_permission_delete', 'filename' => 'install.php'));
            }
        } else {
            try {
                rename('install.php',$newname . '.php');
            } catch (Exception $e) {
                return xarTplModule('installer','user','errors',array('layout' => 'no_permission_rename', 'filename' => 'install.php'));
            }
        }
    }

    // Install script is still there. Create a reminder block
    if (file_exists('install.php')) {

        // get the left blockgroup block id
        $leftBlockgroup = xarMod::apiFunc('blocks', 'user', 'get', array('name' => 'left'));
        if ($leftBlockgroup == false) {
            $msg = xarML("Blockgroup 'left' not found.");
            throw new Exception($msg);
        }
        $leftBlockgroupID = $leftBlockgroup['bid'];
        assert('is_numeric($leftBlockgroupID);');

        $menuBlockType = xarMod::apiFunc('blocks', 'user', 'getblocktype',
                                     array('module'  => 'base',
                                           'type'=> 'menu'));

        $menuBlockTypeId = $menuBlockType['tid'];

        $content['marker'] = '[x]';                                           // create the user menu
        $content['displaymodules'] = 'All';
        $content['modulelist'] = '';
        $content['content'] = '';

        if (!xarMod::apiFunc('blocks', 'user', 'get', array('name'  => 'mainmenu'))) {
            if (!xarMod::apiFunc('blocks', 'admin', 'create_instance',
                          array('title' => 'Main Menu',
                                'name'  => 'mainmenu',
                                'type'  => $menuBlockTypeId,
                                'groups' => array(array('id' => $leftBlockgroupID,)),
                                'content' => $content,
                                'state' => 2))) {
                return;
            }
        }

        // get the admin blockgroup block id
        $adminBlockgroup = xarMod::apiFunc('blocks', 'user', 'get', array('name' => 'admin'));
        if ($adminBlockgroup == false) {
            $msg = xarML("Blockgroup 'admin' not found.");
            throw new Exception($msg);
        }
        $adminBlockgroupID = $adminBlockgroup['bid'];
        assert('is_numeric($adminBlockgroupID);');

        $now = time();

        $reminder = array(
            'content_text' => 'Please delete install.php from your webroot.',
            'expire' => $now + 259200,
        );

        $htmlBlockType = xarMod::apiFunc('blocks', 'user', 'getblocktype',
                                     array('module'  => 'base',
                                           'type'    => 'content'));

        $htmlBlockTypeId = $htmlBlockType['tid'];

        if (!xarMod::apiFunc('blocks', 'user', 'get', array('name'  => 'reminder'))) {
            if (!xarMod::apiFunc('blocks', 'admin', 'create_instance',
                               array('title'    => 'Reminder',
                                     'name'     => 'reminder',
                                     'content'  => $reminder,
                                     'type'     => $htmlBlockTypeId,
                                     'groups'   => array(array('id'      => $adminBlockgroupID,)),
                                     'state'    => 2))) {
                return;
            }
        }

        // get block instances for the admin blockgroup
        $instances = xarMod::apiFunc('blocks', 'user', 'getall',
            array('order' => 'group','gid' => $adminBlockgroupID));
        $group_instance_order = array();
        $reminderBlock = xarMod::apiFunc('blocks', 'user', 'get', array('name'  => 'reminder'));
        // put the reminder at the top of the group
        $group_instance_order[] = $reminderBlock['bid'];
        foreach ($instances as $inst) {
            if ($inst['bid'] == $reminderBlock['bid']) continue;
            $group_instance_order[] = $inst['bid'];
        }
        if (!xarModAPIFunc('blocks', 'admin', 'update_group',
            array(
                'id' => $adminBlockgroupID,
                'instance_order' => $group_instance_order)
            )
        ) return;

    }

    xarUserLogOut();
    // log in admin user
    $uname = xarModVars::get('roles','lastuser');
    $pass = xarModVars::get('roles','adminpass');

    if (!xarUserLogIn($uname, $pass, 0)) {
        $msg = xarML('Cannot log in the default administrator. Check your setup.');
        throw new Exception($msg);
    }


    xarModVars::delete('roles','adminpass');

    // get the right blockgroup block id
    $rightBlockgroup = xarMod::apiFunc('blocks', 'user', 'get', array('name' => 'right'));
    if ($rightBlockgroup == false) {
        $msg = xarML("Blockgroup 'right' not found.");
        throw new Exception($msg);
    }
    $rightBlockgroupID = $rightBlockgroup['bid'];
    assert('is_numeric($rightBlockgroupID);');

    $loginBlockTypeId = xarMod::apiFunc('blocks','admin','register_block_type',
                    array('modName' => 'authsystem', 'blockType' => 'login'));
    if (empty($loginBlockTypeId)) {
        // FIXME: shouldn't we raise an exception here?
        return;
    }

    if (!xarMod::apiFunc('blocks', 'user', 'get', array('name'  => 'login'))) {
        if (xarMod::apiFunc('blocks', 'admin', 'create_instance',
                           array('title'    => 'Login',
                                 'name'     => 'login',
                                 'type'     => $loginBlockTypeId,
                                 'groups'    => array(array('id'     => $rightBlockgroupID)),
                                 'state'    => 2))) {
        } else {
            throw new Exception('Could not create login block');
        }
    }

    // get the header blockgroup block id
    $headerBlockgroup = xarMod::apiFunc('blocks', 'user', 'get', array('name' => 'header'));
    if ($headerBlockgroup == false) {
        $msg = xarML("Blockgroup 'header' not found.");
        throw new Exception($msg);
    }
    $headerBlockgroupID = $headerBlockgroup['bid'];
    assert('is_numeric($headerBlockgroupID);');

    $metaBlockType = xarMod::apiFunc('blocks', 'user', 'getblocktype',
                                   array('module' => 'themes',
                                         'type'   => 'meta'));

    $metaBlockTypeId = $metaBlockType['tid'];

    if (!xarMod::apiFunc('blocks', 'user', 'get', array('name'  => 'meta'))) {
        if (xarMod::apiFunc('blocks', 'admin', 'create_instance',
                           array('title'    => 'Meta',
                                 'name'     => 'meta',
                                 'type'     => $metaBlockTypeId,
                                 'groups'    => array(array('id'      => $headerBlockgroupID)),
                                 'state'    => 2))) {
        } else {
            throw new Exception('Could not create meta block');
        }
    }

    xarMod::apiFunc('dynamicdata','admin','importpropertytypes', array('flush' => true));

    $data['language']    = $install_language;
    $data['phase'] = 10;
    $data['phase_label'] = xarML('Step Ten');
    $data['finalurl'] = xarModURL('installer', 'admin', 'finish');

    return $data;
}

?>