<?php

/**
 * @package modules\roles
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Roles\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Roles\AdminGui;
use Xaraya\Modules\Roles\UserApi;
use Xaraya\Modules\Roles\AdminApi;
use DirectoryNotFoundException;
use Query;
use xarDB;
use xarMod;
use xarModVars;
use xarRoles;
use xarSec;
use xarSecurity;
use xarSession;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles admin createmail function
 * @extends MethodClass<AdminGui>
 */
class CreatemailMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     *
     * @return array|void data for the template display
     * @see AdminGui::createmail()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // TODO allow selection by group or user or all users.
        // Security
        if (!xarSecurity::check('MailRoles')) {
            return;
        }

        $data = [];
        $this->var()->find('id', $id, 'int:0:', -1);
        $this->var()->find('ids', $ids);
        $this->var()->find('state', $state, 'int:0:', xarRoles::ROLES_STATE_ALL);
        $this->var()->find('startnum', $startnum, 'int:1:', 1);
        $this->var()->find('order', $data['order'], 'str:0:', 'name');
        $this->var()->find('includesubgroups', $data['includesubgroups'], 'int:0:', 0);
        $this->var()->find('mailtype', $data['mailtype'], 'str:0:', 'blank');
        $this->var()->find('selstyle', $selstyle, 'isset', 0);

        // what type of email: a selection or a single email?
        if ($id < 1) {
            $type = 'selection';
        } else {
            $role  = xarRoles::get($id);
            $type  = ($role->getType() == xarRoles::ROLES_GROUPTYPE) ? 'selection' : 'single';
        }

        sys::import('xaraya.structures.query');
        $xartable = xarDB::getTables();
        if ($type == 'single') {
            $id = $role->getID();
            $data['users'][$role->getID()] =
                ['id'       => $id,
                    'name'     => $role->getName(),
                    'uname'    => $role->getUser(),
                    'email'    => $role->getEmail(),
                    'status'   => $role->getState(),
                    'date_reg' => $role->getDateReg(),
                ];

            if ($selstyle == 0) {
                $selstyle = 2;
            }
            // Create a query to send to sendmail
            $q = new Query('SELECT');
            $q->addtable($xartable['roles'], 'r');
            $q->addfields(['r.id AS id',
                'r.name AS name',
                'r.uname AS uname',
                'r.email AS email',
                'r.state AS state',
                'r.date_reg AS date_reg']);
            $q->eq('r.id', $id);
            xarSession::setVar('rolesquery', serialize($q));
        } else {
            if ($selstyle == 0) {
                $selstyle = 1;
            }

            // Get the current query or create a new one if need be
            if ($id == -1) {
                $q = new Query();
                $stored = xarSession::getVar('rolesquery') ?? 'a:0:{}';
                $q = unserialize($stored);
            }
            if (empty($q->tables)) {
                $q = new Query('SELECT');
                $q->addtable($xartable['roles'], 'r');
            }
            $q->addfields(['r.id AS id',
                'r.name AS name',
                'r.uname AS uname',
                'r.email AS email',
                'r.state AS state',
                'r.date_reg AS date_reg']);
            $q->eq('r.itemtype', xarRoles::ROLES_USERTYPE);
            $q->ne('r.email', '');
            // Set the paging and order stuff for this particular page
            $numitems = (int) xarModVars::get('roles', 'items_per_page');
            $q->setrowstodo($numitems);
            $q->setstartat($startnum);
            $q->setorder($data['order']);

            // Add state
            if ($id != -1) {
                $q->removecondition('state');
                if ($state == xarRoles::ROLES_STATE_CURRENT) {
                    $q->ne('state', xarRoles::ROLES_STATE_DELETED);
                } elseif ($state == xarRoles::ROLES_STATE_ALL) {
                } else {
                    $q->eq('state', $state);
                }
            } else {
                $state = xarRoles::ROLES_STATE_ALL;
            }

            if ($id != -1 && $id != 0) {
                if ($role->getType() == xarRoles::ROLES_GROUPTYPE) {
                    // If a group was chosen, get only the users of that group
                    $q->addtable($xartable['rolemembers'], 'rm');
                    $q->join('r.id', 'rm.role_id');
                    $q->eq('rm.parent_id', $id);
                } else {
                    $q->eq('r.id', $id);
                }
            }

            // Save the query so we can reuse it somewhere
            xarSession::setVar('rolesquery', serialize($q));
            // open a connection and run the query
            $q->run();

            foreach ($q->output() as $role) {
                $data['users'][$role['id']] =
                    ['id'      => $role['id'],
                        'name'     => $role['name'],
                        'uname'    => $role['uname'],
                        'email'    => $role['email'],
                        'status'   => $role['state'],
                        'date_reg' => $role['date_reg'],
                        'frozen'   => !xarSecurity::check('EditRoles', 0, 'Roles', $role['name']),
                    ];
            }

            // Check if we also want to send to subgroups
            // In this case we'll just pick out the descendants in the same state
            if ($id != 0 && ($data['includesubgroups'] == 1)) {
                $parentgroup = xarRoles::get($id);
                $descendants = $parentgroup->getDescendants($state);

                foreach ($descendants as $key => $user) {
                    if (xarSecurity::check('EditRoles', 0, 'Roles', $user->getName())) {
                        if (in_array($state, [$user->getState(),xarRoles::ROLES_STATE_ALL])) {
                            $data['users'][$user->getID()] =
                                ['id'      => $user->getID(),
                                    'name'     => $user->getName(),
                                    'uname'    => $user->getUser(),
                                    'email'    => $user->getEmail(),
                                    'status'   => $user->getState(),
                                    'date_reg' => $user->getDateReg(),
                                ];
                        }
                    }
                }
            }
        }

        // Get the list of available templates
        $messaginghome = sys::varpath() . "/messaging/roles";
        if (!file_exists($messaginghome)) {
            throw new DirectoryNotFoundException($messaginghome);
        }

        $dd = opendir($messaginghome);
        $templates = [['key' => 'blank', 'value' => xarML('Empty')]];
        while ($filename = readdir($dd)) {
            if (!is_dir($messaginghome . "/" . $filename)) {
                $pos = strpos($filename, '-message.xt');
                if (!($pos === false)) {
                    $templatename = substr($filename, 0, $pos);
                    $templatelabel = ucfirst($templatename);
                    $templates[] = ['key' => $templatename, 'value' => $templatelabel];
                }
            }
        }
        closedir($dd);

        $data['templates'] = $templates;
        $data['type']      = $type;
        $data['selstyle']  = $selstyle;
        $data['id']       = $id;
        $data['state']     = $state;
        $data['authid']    = xarSec::genAuthKey();
        $data['groups']    = $userapi->getallgroups();
        //selstyle
        $data['style'] = ['1' => xarML('No'),
            '2' => xarML('Yes'),
        ];
        if (isset($data['users'])) {
            $data['totalselected'] = count($data['users']);
        }
        //templates select
        if ($data['mailtype'] == 'blank') {
            $data['subject'] = '';
            $data['message'] = '';
        } else {
            $strings = $adminapi->getmessagestrings(['template' => $data['mailtype']]);
            if (!isset($strings)) {
                return;
            }

            $data['subject'] = $strings['subject'];
            $data['message'] = $strings['message'];
        }

        return $data;
    }
}
