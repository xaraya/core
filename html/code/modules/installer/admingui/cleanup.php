<?php

/**
 * @package modules\installer
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Installer\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Installer\AdminGui;
use Exception;
use ixarBlock;

/**
 * installer admin cleanup function
 * @extends MethodClass<AdminGui>
 */
class CleanupMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Installer
     * @package modules\installer\installer
     * @subpackage installer
     * @category Xaraya Web Applications Framework
     * @version 2.4.0
     * @copyright see the html/credits.html file in this release
     * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
     * @link http://xaraya.info/index.php/release/200.html
     * @see AdminGui::cleanup()
     */
    public function __invoke(array $args = [])
    {
        if (!file_exists('install.php')) {
            throw new Exception('Already installed');
        }
        $this->var()->find('install_language', $install_language, 'str::', 'en_US.utf-8');
        $this->tpl()->setThemeName('installer');

        $this->var()->find('remove', $remove, 'checkbox', false);
        $this->var()->find('rename', $rename, 'checkbox', false);
        $this->var()->find('newname', $newname, 'str', '');

        if ($remove) {
            try {
                unlink('install.php');
            } catch (Exception $e) {
                return $this->tpl()->module('installer', 'user', 'errors', ['layout' => 'no_permission_delete', 'filename' => 'install.php']);
            }
        } elseif ($rename) {
            if (empty($newname)) {
                try {
                    unlink('install.php');
                } catch (Exception $e) {
                    return $this->tpl()->module('installer', 'user', 'errors', ['layout' => 'no_permission_delete', 'filename' => 'install.php']);
                }
            } else {
                try {
                    rename('install.php', $newname . '.php');
                } catch (Exception $e) {
                    return $this->tpl()->module('installer', 'user', 'errors', ['layout' => 'no_permission_rename', 'filename' => 'install.php']);
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
        if (!$this->mod()->apiFunc('blocks', 'types', 'refresh', ['refresh' => true])) {
            return;
        }

        // get the default blockgroup block type info
        $group_type = $this->mod()->apiFunc(
            'blocks',
            'types',
            'getitem',
            ['type' => 'blockgroup', 'module' => 'blocks']
        );

        // register groups (instances of blockgroup type - groupname => box_template )
        $groups =  [
            'left'   => null,
            'right'  => 'right',
            'header' => 'header',
            'admin'  => null,
            'center' => 'center',
            'topnav' => 'topnav',
        ];

        foreach ($groups as $name => $template) {
            if (!$this->mod()->apiFunc('blocks', 'instances', 'getitem', ['name' => $name])) {
                $content = $group_type['type_info'];
                $content['box_template'] = $template;
                if (!$this->mod()->apiFunc(
                    'blocks',
                    'instances',
                    'createitem',
                    [
                        'type_id' => $group_type['type_id'],
                        'name' => $name,
                        'title' => '',
                        'state' => ixarBlock::BLOCK_STATE_VISIBLE,
                        'content' => $content,
                    ]
                )) {
                    return;
                }
            }
        }

        // get info for left group instance
        $left_group = $this->mod()->apiFunc('blocks', 'instances', 'getitem', ['name' => 'left']);

        // see if we have a menu instance
        if (!$this->mod()->apiFunc('blocks', 'instances', 'getitem', ['name' => 'mainmenu'])) {
            // get the default menu block type info
            $menu_type = $this->mod()->apiFunc(
                'blocks',
                'types',
                'getitem',
                ['type' => 'menu', 'module' => 'base']
            );
            // get an instance of the menu block type
            $menu_block = $this->mod()->apiFunc('blocks', 'blocks', 'getblock', $menu_type);
            // attach the left group to the menu instance
            $menu_block->attachGroup($left_group['block_id']);
            // create menu instance
            if (!$menu_id = $this->mod()->apiFunc(
                'blocks',
                'instances',
                'createitem',
                [
                    'type_id' => $menu_type['type_id'],
                    'name' => 'mainmenu',
                    'title' => 'Main Menu',
                    'state' => ixarBlock::BLOCK_STATE_VISIBLE,
                    'content' => $menu_block->storeContent(),
                ]
            )) {
                return;
            }
        }
        // add menu instance to left block
        if (!empty($menu_id)) {
            // get an instance of the left group
            $left_block = $this->mod()->apiFunc('blocks', 'blocks', 'getblock', $left_group);
            // attach menu block to left group instance
            $left_block->attachInstance($menu_id);
            // update left block instance
            if (!$this->mod()->apiFunc(
                'blocks',
                'instances',
                'updateitem',
                [
                    'block_id' => $left_group['block_id'],
                    'content' => $left_block->storeContent(),
                ]
            )) {
                return;
            }
        }

        // get info for right group instance
        $right_group = $this->mod()->apiFunc('blocks', 'instances', 'getitem', ['name' => 'right']);

        // see if we have a login instance
        if (!$this->mod()->apiFunc('blocks', 'instances', 'getitem', ['name' => 'login'])) {
            // get the default login block type info
            $login_type = $this->mod()->apiFunc(
                'blocks',
                'types',
                'getitem',
                ['type' => 'login', 'module' => 'authsystem']
            );
            // get an instance of the login block type
            $login_block = $this->mod()->apiFunc('blocks', 'blocks', 'getblock', $login_type);
            // attach the right group to the login instance
            $login_block->attachGroup($right_group['block_id']);
            // create login instance
            if (!$login_id = $this->mod()->apiFunc(
                'blocks',
                'instances',
                'createitem',
                [
                    'type_id' => $login_type['type_id'],
                    'name' => 'login',
                    'title' => 'Login',
                    'state' => ixarBlock::BLOCK_STATE_VISIBLE,
                    'content' => $login_block->storeContent(),
                ]
            )) {
                return;
            }
        }
        // add login instance to right block
        if (!empty($login_id)) {
            // get an instance of the right group
            $right_block = $this->mod()->apiFunc('blocks', 'blocks', 'getblock', $right_group);
            // attach login block to right group instance
            $right_block->attachInstance($login_id);
            // update right block instance
            if (!$this->mod()->apiFunc(
                'blocks',
                'instances',
                'updateitem',
                [
                    'block_id' => $right_group['block_id'],
                    'content' => $right_block->storeContent(),
                ]
            )) {
                return;
            }
        }

        // get info for header group instance
        $header_group = $this->mod()->apiFunc('blocks', 'instances', 'getitem', ['name' => 'header']);

        // see if we have a meta instance
        if (!$this->mod()->apiFunc('blocks', 'instances', 'getitem', ['name' => 'meta'])) {
            // get the default meta block type info
            $meta_type = $this->mod()->apiFunc(
                'blocks',
                'types',
                'getitem',
                ['type' => 'meta', 'module' => 'themes']
            );
            // get an instance of the meta block type
            $meta_block = $this->mod()->apiFunc('blocks', 'blocks', 'getblock', $meta_type);
            // attach the header group to the meta instance
            $meta_block->attachGroup($header_group['block_id']);
            // create meta instance
            if (!$meta_id = $this->mod()->apiFunc(
                'blocks',
                'instances',
                'createitem',
                [
                    'type_id' => $meta_type['type_id'],
                    'name' => 'meta',
                    'state' => ixarBlock::BLOCK_STATE_VISIBLE,
                    'content' => $meta_block->storeContent(),
                ]
            )) {
                return;
            }
        }
        // add meta instance to header block
        if (!empty($meta_id)) {
            // get an instance of the header group
            $header_block = $this->mod()->apiFunc('blocks', 'blocks', 'getblock', $header_group);
            // attach meta block to header group instance
            $header_block->attachInstance($meta_id);
            // update header block instance
            if (!$this->mod()->apiFunc(
                'blocks',
                'instances',
                'updateitem',
                [
                    'block_id' => $header_group['block_id'],
                    'content' => $header_block->storeContent(),
                ]
            )) {
                return;
            }
        }

        // get info for admin group instance
        $admin_group = $this->mod()->apiFunc('blocks', 'instances', 'getitem', ['name' => 'admin']);

        // see if we have an adminmenu instance
        if (!$this->mod()->apiFunc('blocks', 'instances', 'getitem', ['name' => 'adminpanel'])) {
            // get the default adminmenu block type info
            $adminmenu_type = $this->mod()->apiFunc(
                'blocks',
                'types',
                'getitem',
                ['type' => 'adminmenu', 'module' => 'base']
            );
            // get an instance of the adminmenu block type
            $adminmenu_block = $this->mod()->apiFunc('blocks', 'blocks', 'getblock', $adminmenu_type);
            // attach the admin group to the adminmenu instance
            $adminmenu_block->attachGroup($admin_group['block_id']);
            // create adminmenu instance
            if (!$adminmenu_id = $this->mod()->apiFunc(
                'blocks',
                'instances',
                'createitem',
                [
                    'type_id' => $adminmenu_type['type_id'],
                    'name' => 'adminpanel',
                    'title' => 'Admin',
                    'state' => ixarBlock::BLOCK_STATE_VISIBLE,
                    'content' => $adminmenu_block->storeContent(),
                ]
            )) {
                return;
            }
        }

        // if install.php still exists, set a reminder instance
        if (!$this->mod()->apiFunc('blocks', 'instances', 'getitem', ['name' => 'reminder']) && (file_exists('install.php') || file_exists('upgrade.php'))) {
            // get the default reminder block type info
            $reminder_type = $this->mod()->apiFunc(
                'blocks',
                'types',
                'getitem',
                ['type' => 'content', 'module' => 'base']
            );
            // get an instance of the reminder block type
            $reminder_block = $this->mod()->apiFunc('blocks', 'blocks', 'getblock', $reminder_type);
            // attach the admin group to the reminder instance
            $reminder_block->attachGroup($admin_group['block_id']);
            // set content
            $reminder_content = $reminder_block->storeContent();
            $reminder_content['content_text'] = 'if (is_file("install.php")) echo "<div>Please delete install.php from your web root directory.</div>";
    if (is_file("upgrade.php")) echo "<div>Please delete upgrade.php from your web root directory.</div>";';
            $reminder_content['content_type'] = 'php';
            $reminder_content['expire'] = time() + 259200;
            // create reminder instance
            if (!$reminder_id = $this->mod()->apiFunc(
                'blocks',
                'instances',
                'createitem',
                [
                    'type_id' => $reminder_type['type_id'],
                    'name' => 'reminder',
                    'title' => 'Reminder',
                    'state' => ixarBlock::BLOCK_STATE_VISIBLE,
                    'content' => $reminder_content,
                ]
            )) {
                return;
            }
        }

        // add adminmenu and/or reminder instance to admin block
        if (!empty($adminmenu_id) || !empty($reminder_id)) {
            // get an instance of the admin group
            $admin_block = $this->mod()->apiFunc('blocks', 'blocks', 'getblock', $admin_group);
            // attach reminder block to admin group instance
            if (!empty($reminder_id)) {
                $admin_block->attachInstance($reminder_id);
            }
            // attach adminmenu block to admin group instance
            if (!empty($adminmenu_id)) {
                $admin_block->attachInstance($adminmenu_id);
            }
            // update admin block instance
            if (!$this->mod()->apiFunc(
                'blocks',
                'instances',
                'updateitem',
                [
                    'block_id' => $admin_group['block_id'],
                    'content' => $admin_block->storeContent(),
                ]
            )) {
                return;
            }
        }
        /**
         * End SoloBlocks
        **/

        $this->user()->logOut();
        // log in admin user
        $uname = $this->mod('roles')->getVar('lastuser');
        $pass = $this->mod('roles')->getVar('adminpass');

        if (!$this->user()->logIn($uname, $pass, 0)) {
            $msg = $this->ml('Cannot log in the default administrator. Check your setup.');
            throw new Exception($msg);
        }

        $this->mod('roles')->delVar('adminpass');

        $this->mod()->apiFunc('dynamicdata', 'admin', 'importpropertytypes', ['flush' => true]);

        $data['language']    = $install_language;
        $data['phase'] = 10;
        $data['phase_label'] = $this->ml('Step Ten');
        $data['finalurl'] = $this->ctl()->getModuleURL('installer', 'admin', 'finish');

        return $data;
    }
}
