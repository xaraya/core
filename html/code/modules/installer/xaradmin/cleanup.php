<?php
/**
 * Installer
 *
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/200.html
 */

/* Do not allow this script to run if the install script has been removed.
 * This assumes the install.php and index.php are in the same directory.
 * @author Paul Rosania
 * @author Marcel van der Boom <marcel@hsdev.com>
 * @return array data for the template display
 */

function installer_admin_cleanup()
{
    if (!file_exists('install.php')) { throw new Exception('Already installed');}
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);
    xarTpl::setThemeName('installer');

    xarVarFetch('remove', 'checkbox', $remove, false, XARVAR_NOT_REQUIRED);
    xarVarFetch('rename', 'checkbox', $rename, false, XARVAR_NOT_REQUIRED);
    xarVarFetch('newname', 'str', $newname, '', XARVAR_NOT_REQUIRED);

    if ($remove) {
        try {
            unlink('install.php');
        } catch (Exception $e) {
            return xarTpl::module('installer','user','errors',array('layout' => 'no_permission_delete', 'filename' => 'install.php'));
        }
    } elseif ($rename) {
        if (empty($newname)) {
            try {
                unlink('install.php');
            } catch (Exception $e) {
                return xarTpl::module('installer','user','errors',array('layout' => 'no_permission_delete', 'filename' => 'install.php'));
            }
        } else {
            try {
                rename('install.php',$newname . '.php');
            } catch (Exception $e) {
                return xarTpl::module('installer','user','errors',array('layout' => 'no_permission_rename', 'filename' => 'install.php'));
            }
        }
    }


/**
 * SoloBlocks Scenario
 *
 * Blocks module now registers block types automatically
 * The following code takes care of setting up groups and block instances
**/
    // refresh block types (auto registers available solo/module block types) 
    if (!xarMod::apiFunc('blocks', 'types', 'refresh', array('refresh' => true))) return;

    // get the default blockgroup block type info
    $group_type = xarMod::apiFunc('blocks', 'types', 'getitem', 
        array('type' => 'blockgroup', 'module' => 'blocks'));

    // register groups (instances of blockgroup type - groupname => box_template )
    $groups = array (
        'left'   => null,                          
        'right'  => 'right',
        'header' => 'header',
        'admin'  => null,
        'center' => 'center',
        'topnav' => 'topnav'
    );
    
    foreach ($groups as $name => $template) {
        if (!xarMod::apiFunc('blocks', 'instances', 'getitem', array('name' => $name))) {
            $content = $group_type['type_info'];
            $content['box_template'] = $template;
            if (!xarMod::apiFunc('blocks', 'instances', 'createitem',
                array(
                    'type_id' => $group_type['type_id'],
                    'name' => $name,
                    'title' => '',
                    'state' => xarBlock::BLOCK_STATE_VISIBLE,
                    'content' => $content,
                ))) return;
        }
    }             
    
    // get info for left group instance 
    $left_group = xarMod::apiFunc('blocks', 'instances', 'getitem', array('name' => 'left'));

    // see if we have a menu instance
    if (!xarMod::apiFunc('blocks', 'instances', 'getitem', array('name' => 'mainmenu'))) {
        // get the default menu block type info
        $menu_type = xarMod::apiFunc('blocks', 'types', 'getitem', 
            array('type' => 'menu', 'module' => 'base'));
        // get an instance of the menu block type
        $menu_block = xarMod::apiFunc('blocks', 'blocks', 'getobject', $menu_type);
        // attach the left group to the menu instance
        $menu_block->attachGroup($left_group['block_id']);
        // create menu instance
        if (!$menu_id =xarMod::apiFunc('blocks', 'instances', 'createitem',
            array(
                'type_id' => $menu_type['type_id'],
                'name' => 'mainmenu',
                'title' => 'Main Menu',
                'state' => xarBlock::BLOCK_STATE_VISIBLE,
                'content' => $menu_block->storeContent(),
            ))) return;
    }
    // add menu instance to left block
    if (!empty($menu_id)) {
        // get an instance of the left group 
        $left_block = xarMod::apiFunc('blocks', 'blocks', 'getobject', $left_group);
        // attach menu block to left group instance
        $left_block->attachInstance($menu_id);
        // update left block instance
        if (!xarMod::apiFunc('blocks', 'instances', 'updateitem',
            array(
                'block_id' => $left_group['block_id'],
                'content' => $left_block->storeContent(),
            ))) return;
    }

    // get info for right group instance 
    $right_group = xarMod::apiFunc('blocks', 'instances', 'getitem', array('name' => 'right'));

    // see if we have a login instance
    if (!xarMod::apiFunc('blocks', 'instances', 'getitem', array('name' => 'login'))) {
        // get the default login block type info
        $login_type = xarMod::apiFunc('blocks', 'types', 'getitem', 
            array('type' => 'login', 'module' => 'authsystem'));
        // get an instance of the login block type
        $login_block = xarMod::apiFunc('blocks', 'blocks', 'getobject', $login_type);
        // attach the right group to the login instance
        $login_block->attachGroup($right_group['block_id']);
        // create login instance
        if (!$login_id =xarMod::apiFunc('blocks', 'instances', 'createitem',
            array(
                'type_id' => $login_type['type_id'],
                'name' => 'login',
                'title' => 'Login',
                'state' => xarBlock::BLOCK_STATE_VISIBLE,
                'content' => $login_block->storeContent(),
            ))) return;
    }
    // add login instance to right block
    if (!empty($login_id)) {
        // get an instance of the right group 
        $right_block = xarMod::apiFunc('blocks', 'blocks', 'getobject', $right_group);
        // attach login block to right group instance
        $right_block->attachInstance($login_id);
        // update right block instance
        if (!xarMod::apiFunc('blocks', 'instances', 'updateitem',
            array(
                'block_id' => $right_group['block_id'],
                'content' => $right_block->storeContent(),
            ))) return;
    }

    // get info for header group instance 
    $header_group = xarMod::apiFunc('blocks', 'instances', 'getitem', array('name' => 'header'));

    // see if we have a meta instance
    if (!xarMod::apiFunc('blocks', 'instances', 'getitem', array('name' => 'meta'))) {
        // get the default meta block type info
        $meta_type = xarMod::apiFunc('blocks', 'types', 'getitem', 
            array('type' => 'meta', 'module' => 'themes'));
        // get an instance of the meta block type
        $meta_block = xarMod::apiFunc('blocks', 'blocks', 'getobject', $meta_type);
        // attach the header group to the meta instance
        $meta_block->attachGroup($header_group['block_id']);
        // create meta instance
        if (!$meta_id =xarMod::apiFunc('blocks', 'instances', 'createitem',
            array(
                'type_id' => $meta_type['type_id'],
                'name' => 'meta',
                'state' => xarBlock::BLOCK_STATE_VISIBLE,
                'content' => $meta_block->storeContent(),
            ))) return;
    }
    // add meta instance to header block
    if (!empty($meta_id)) {
        // get an instance of the header group 
        $header_block = xarMod::apiFunc('blocks', 'blocks', 'getobject', $header_group);
        // attach meta block to header group instance
        $header_block->attachInstance($meta_id);
        // update header block instance
        if (!xarMod::apiFunc('blocks', 'instances', 'updateitem',
            array(
                'block_id' => $header_group['block_id'],
                'content' => $header_block->storeContent(),
            ))) return;
    }

    // get info for admin group instance 
    $admin_group = xarMod::apiFunc('blocks', 'instances', 'getitem', array('name' => 'admin'));

    // see if we have an adminmenu instance
    if (!xarMod::apiFunc('blocks', 'instances', 'getitem', array('name' => 'adminpanel'))) {
        // get the default adminmenu block type info
        $adminmenu_type = xarMod::apiFunc('blocks', 'types', 'getitem', 
            array('type' => 'adminmenu', 'module' => 'base'));
        // get an instance of the adminmenu block type
        $adminmenu_block = xarMod::apiFunc('blocks', 'blocks', 'getobject', $adminmenu_type);
        // attach the admin group to the adminmenu instance
        $adminmenu_block->attachGroup($admin_group['block_id']);
        // create adminmenu instance
        if (!$adminmenu_id =xarMod::apiFunc('blocks', 'instances', 'createitem',
            array(
                'type_id' => $adminmenu_type['type_id'],
                'name' => 'adminpanel',
                'title' => 'Admin',
                'state' => xarBlock::BLOCK_STATE_VISIBLE,
                'content' => $adminmenu_block->storeContent(),
            ))) return;
    }

    // if install.php still exists, set a reminder instance
    if (!xarMod::apiFunc('blocks', 'instances', 'getitem', array('name' => 'reminder')) && (file_exists('install.php') || file_exists('upgrade.php'))) {
        // get the default reminder block type info
        $reminder_type = xarMod::apiFunc('blocks', 'types', 'getitem', 
            array('type' => 'content', 'module' => 'base'));
        // get an instance of the reminder block type
        $reminder_block = xarMod::apiFunc('blocks', 'blocks', 'getobject', $reminder_type);
        // attach the admin group to the reminder instance
        $reminder_block->attachGroup($admin_group['block_id']);
        // set content
        $reminder_content = $reminder_block->storeContent();
        $reminder_content['content_text'] = 'if (is_file("install.php")) echo "<div>Please delete install.php from your web root directory.</div>";
if (is_file("upgrade.php")) echo "<div>Please delete upgrade.php from your web root directory.</div>";';
        $reminder_content['content_type'] = 'php';
        $reminder_content['expire'] = time() + 259200;
        // create reminder instance
        if (!$reminder_id =xarMod::apiFunc('blocks', 'instances', 'createitem',
            array(
                'type_id' => $reminder_type['type_id'],
                'name' => 'reminder',
                'title' => 'Reminder',
                'state' => xarBlock::BLOCK_STATE_VISIBLE,
                'content' => $reminder_content,
            ))) return;
    }

    // add adminmenu and/or reminder instance to admin block
    if (!empty($adminmenu_id) || !empty($reminder_id)) {
        // get an instance of the admin group 
        $admin_block = xarMod::apiFunc('blocks', 'blocks', 'getobject', $admin_group);
        // attach reminder block to admin group instance
        if (!empty($reminder_id))
            $admin_block->attachInstance($reminder_id);
        // attach adminmenu block to admin group instance
        if (!empty($adminmenu_id)) 
            $admin_block->attachInstance($adminmenu_id);
        // update admin block instance
        if (!xarMod::apiFunc('blocks', 'instances', 'updateitem',
            array(
                'block_id' => $admin_group['block_id'],
                'content' => $admin_block->storeContent(),
            ))) return;
    }
/**
 * End SoloBlocks
**/

    xarUserLogOut();
    // log in admin user
    $uname = xarModVars::get('roles','lastuser');
    $pass = xarModVars::get('roles','adminpass');

    if (!xarUserLogIn($uname, $pass, 0)) {
        $msg = xarML('Cannot log in the default administrator. Check your setup.');
        throw new Exception($msg);
    }


    xarModVars::delete('roles','adminpass');

    xarMod::apiFunc('dynamicdata','admin','importpropertytypes', array('flush' => true));

    $data['language']    = $install_language;
    $data['phase'] = 10;
    $data['phase_label'] = xarML('Step Ten');
    $data['finalurl'] = xarModURL('installer', 'admin', 'finish');

    return $data;
}

?>