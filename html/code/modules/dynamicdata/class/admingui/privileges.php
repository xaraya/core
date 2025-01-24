<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\DataObject\AdminGui;

use Xaraya\DataObject\MethodClass;
use Xaraya\DataObject\AdminGui;
use Xaraya\DataObject\UserApi;
use DataObjectFactory;
use xarController;
use xarMod;
use xarSecurity;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * dynamicdata admin privileges function
 * @extends MethodClass<AdminGui>
 */
class PrivilegesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Manage definition of instances for privileges (unfinished)
     * @return array|bool|void data for the template display
     * @see AdminGui::privileges()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        // Security
        if (!$this->sec()->checkAccess('AdminDynamicData')) {
            return;
        }

        extract($args);

        if (!$this->var()->find('objectid', $objectid, 'id')) {
            return;
        }
        if (!$this->var()->find('moduleid', $moduleid, 'str', 0)) {
            return;
        } // empty, 'All', numeric or modulename
        if (!$this->var()->find('itemtype', $itemtype, 'str', 0)) {
            return;
        } // empty, 'All', numeric
        if (!$this->var()->find('itemid', $itemid, 'str', 0)) {
            return;
        } // empty, 'All', numeric
        if (!$this->var()->find('apply', $apply, 'str', false)) {
            return;
        } // boolean?
        if (!$this->var()->find('extpid', $extpid, 'str', '')) {
            return;
        } // empty, 'All', numeric ?
        if (!$this->var()->find('extname', $extname, 'str', '')) {
            return;
        } // ?
        if (!$this->var()->find('extrealm', $extrealm, 'str', '')) {
            return;
        } // ?
        if (!$this->var()->find('extmodule', $extmodule, 'str', '')) {
            return;
        } // ?
        if (!$this->var()->get('extcomponent', $extcomponent, 'enum:All:Item:Field:Type')) {
            return;
        } // FIXME: is 'Type' needed?
        if (!$this->var()->find('extinstance', $extinstance, 'str:1', '')) {
            return;
        } // somthing:somthing:somthing or empty
        if (!$this->var()->get('extlevel', $extlevel, 'str:1')) {
            return;
        }

        // TODO: combine 'Item' and 'Type' instances someday ?

        if (!empty($extinstance)) {
            $parts = explode(':', $extinstance);
            if ($extcomponent == 'Item') {
                if (count($parts) > 0 && !empty($parts[0])) {
                    $moduleid = $parts[0];
                }
                if (count($parts) > 1 && !empty($parts[1])) {
                    $itemtype = $parts[1];
                }
                if (count($parts) > 2 && !empty($parts[2])) {
                    $itemid = $parts[2];
                }
            } else {
            }
        }

        if ($extcomponent == 'Item') {

            if (empty($moduleid) || $moduleid == 'All') {
                $moduleid = 0;
            } elseif (!is_numeric($moduleid)) { // for pre-wizard instances
                $module_id = xarMod::getRegID($moduleid);
                if (!empty($module_id)) {
                    $moduleid = $module_id;
                } else {
                    $moduleid = 0;
                }
            }
            if (empty($itemtype) || $itemtype == 'All' || !is_numeric($itemtype)) {
                $itemtype = 0;
            }
            if (empty($itemid) || $itemid == 'All' || !is_numeric($itemid)) {
                $itemid = 0;
            }

            // define the new instance
            $newinstance = [];
            $newinstance[] = empty($moduleid) ? 'All' : $moduleid;
            $newinstance[] = empty($itemtype) ? 'All' : $itemtype;
            $newinstance[] = empty($itemid) ? 'All' : $itemid;

        } else {

            // define the new instance
            $newinstance = [];

        }

        if (!empty($apply)) {
            // create/update the privilege
            $pid = xarMod::apiFunc('privileges', 'admin', 'returnprivilege', [
                'pid' => $extpid,
                'name' => $extname,
                'realm' => $extrealm,
                'module' => $extmodule,
                'component' => $extcomponent,
                'instance' => $newinstance,
                'level' => $extlevel]);
            if (empty($pid)) {
                return; // throw back
            }

            // redirect to the privilege
            $this->ctl()->redirect($this->ctl()->getModuleURL(
                'privileges',
                'admin',
                'modifyprivilege',
                ['id' => $pid]
            ));
            return true;
        }

        // Get objects
        $objects = $this->data()->getObjects();

        // TODO: use object list instead of (or in addition to) module + itemtype

        // Get module list
        $modlist = [];
        // Get a list of all modules - we just want their IDs
        $all_modules = xarMod::apiFunc('modules', 'admin', 'getlist');
        $all_module_ids = [];
        foreach ($all_modules as $this_module) {
            $all_module_ids[] = $this_module['regid'];
        }
        foreach ($objects as $id => $object) {
            $module_id = $object['moduleid'];
            // Check whether the module exists before trying to fetch the details.
            if (in_array($module_id, $all_module_ids)) {
                $modinfo = xarMod::getInfo($module_id);
                $modlist[$module_id] = $modinfo['displayname'];
            }
        }

        if ($extcomponent == 'Item') {
            if (!empty($itemid)) {
                $numitems = $this->ml('probably');
            } elseif (!empty($objectid) || !empty($moduleid)) {
                $numitems = $userapi->countitems(['objectid' => $objectid,
                        'moduleid' => $moduleid,
                        'itemtype' => $itemtype]
                );
                if (empty($numitems)) {
                    $numitems = 0;
                }
            } else {
                $numitems = $this->ml('probably');
            }

        } else { // 'Type'

            $numitems = $this->ml('probably');

        }

        $data = [
            'objectid'     => $objectid,
            'moduleid'     => $moduleid,
            'itemtype'     => $itemtype,
            'itemid'       => $itemid,
            'objectlist'   => $objects,
            'modlist'      => $modlist,
            'numitems'     => $numitems,
            'extpid'       => $extpid,
            'extname'      => $extname,
            'extrealm'     => $extrealm,
            'extmodule'    => $extmodule,
            'extcomponent' => $extcomponent,
            'extlevel'     => $extlevel,
            'extinstance'  => $this->var()->prep(join(':', $newinstance)),
        ];

        $data['refreshlabel'] = $this->ml('Refresh');
        $data['applylabel'] = $this->ml('Finish and Apply to Privilege');

        return $data;
    }
}
