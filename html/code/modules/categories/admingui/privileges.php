<?php

/**
 * @package modules\categories
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Categories\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Categories\AdminGui;
use Xaraya\Modules\Categories\UserApi;
use CategoryWorker;
use DataPropertyMaster;
use Exception;
use xarController;
use xarMod;
use xarPrivileges;
use xarSecurity;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * categories admin privileges function
 * @extends MethodClass<AdminGui>
 */
class PrivilegesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Manage definition of instances for privileges (unfinished)
     * @param array<string,mixed> $args Parameter data array
     * @return array|bool|void Return display data array on success, null on failure.
     * @see AdminGui::privileges()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        // Security Check
        if (!xarSecurity::check('AdminCategories')) {
            return;
        }

        extract($args);

        // fixed params
        if (!xarVar::fetch('cid', 'isset', $cid, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('moduleid', 'isset', $moduleid, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('itemtype', 'isset', $itemtype, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('itemid', 'isset', $itemid, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('apply', 'isset', $apply, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('extpid', 'isset', $extpid, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('extname', 'isset', $extname, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('extrealm', 'isset', $extrealm, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('extmodule', 'isset', $extmodule, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('extcomponent', 'isset', $extcomponent, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('extinstance', 'isset', $extinstance, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('extlevel', 'isset', $extlevel, null, xarVar::DONT_SET)) {
            return;
        }

        sys::import('modules.dynamicdata.class.properties.master');
        /** @var CategoriesProperty $categories */
        $categories = DataPropertyMaster::getProperty(['name' => 'categories']);
        // @checkme is this what you need here?
        //$cids = $categories->returnInput('privcategories');
        $cids = [];
        if ($categories->checkInput('privcategories')) {
            $value = $categories->value;
            if (!empty($value) && is_array($value)) {
                $cids = array_values($value);
            }
        }

        // 'Category' component = All:cid (catname is unused)
        if (!empty($extcomponent) && $extcomponent == 'Category') {

            // check the current instance
            if (!empty($extinstance)) {
                $parts = explode(':', $extinstance);
                if (count($parts) > 0 && !empty($parts[0])) {
                    $catname = $parts[0];
                }
                if (count($parts) > 1 && !empty($parts[1])) {
                    $cid = $parts[1];
                }
            }

            // check the selected category
            // TODO: figure out how to handle more than 1 category in instances
            if (empty($cid) || $cid == 'All' || !is_numeric($cid)) {
                $cid = 0;
            }
            if (empty($cid) && isset($cids) && is_array($cids)) {
                foreach ($cids as $catid) {
                    if (!empty($catid) && is_numeric($catid)) {
                        $cid = $catid;
                        // bail out for now
                        break;
                    }
                }
            }

            // define the new instance
            $newinstance = [];
            if (empty($cid)) {
                $newinstance[] = 'All';
                $newinstance[] = 'All';
            } else {
                $catinfo = $userapi->getcatinfo(['cid' => $cid]);
                if (empty($catinfo)) {
                    $cid = 0;
                    $newinstance[] = 'All';
                    $newinstance[] = 'All';
                } else {
                    $newinstance[] = 'All';
                    $newinstance[] = $cid;
                }
            }

            // TODO: add option to apply this privilege for all child categories too
            //       (once privileges supports this)

            if (!empty($apply)) {
                // create/update the privilege
                $pid = xarPrivileges::external($extpid, $extname, $extrealm, $extmodule, $extcomponent, $newinstance, $extlevel);
                if (empty($pid)) {
                    return; // throw back
                }

                // redirect to the privilege
                xarController::redirect(xarController::URL(
                    'privileges',
                    'admin',
                    'modifyprivilege',
                    ['pid' => $pid]
                ), null, $this->getContext());
                return true;
            }

            $data = [
                'cid'          => $cid,
                'extpid'       => $extpid,
                'extname'      => $extname,
                'extrealm'     => $extrealm,
                'extmodule'    => $extmodule,
                'extcomponent' => $extcomponent,
                'extlevel'     => $extlevel,
                'extinstance'  => xarVar::prepForDisplay(join(':', $newinstance)),
            ];

            $seencid = [];
            if (!empty($cid)) {
                $seencid[$cid] = 1;
            }
            $data['cids'] = $cids;

            $data['refreshlabel'] = xarML('Refresh');
            $data['applylabel'] = xarML('Finish and Apply to Privilege');

            return $data;
        }

        // 'Link' component = moduleid:itemtype:itemid:cid
        if (!empty($extinstance)) {
            $parts = explode(':', $extinstance);
            if (count($parts) > 0 && !empty($parts[0])) {
                $moduleid = $parts[0];
            }
            if (count($parts) > 1 && !empty($parts[1])) {
                $itemtype = $parts[1];
            }
            if (count($parts) > 2 && !empty($parts[2])) {
                $itemid = $parts[2];
            }
            if (count($parts) > 3 && !empty($parts[3])) {
                $cid = $parts[3];
            }
        }

        // Get the list of all modules currently hooked to categories
        $hookedmodlist = xarMod::apiFunc(
            'modules',
            'admin',
            'gethookedmodules',
            ['hookModName' => 'categories']
        );
        if (!isset($hookedmodlist)) {
            $hookedmodlist = [];
        }

        $modlist = [];
        $typelist = [];
        foreach ($hookedmodlist as $modname => $value) {
            if (empty($modname)) {
                continue;
            }
            $modid = xarMod::getRegID($modname);
            if (empty($modid)) {
                continue;
            }
            $modinfo = xarMod::getInfo($modid);
            $modlist[$modid] = $modinfo['displayname'];
            if (!empty($moduleid) && $moduleid == $modid) {
                // Get the list of all item types for this module (if any)
                try {
                    $mytypes = xarMod::apiFunc($modname, 'user', 'getitemtypes');
                } catch (Exception $e) {
                    $mytypes = [];
                }
                if (empty($mytypes)) {
                    $mytypes = [];
                }
                if (!empty($value[0])) {
                    foreach ($mytypes as $id => $type) {
                        $typelist[$id] = $type['label'];
                    }
                } else {
                    foreach ($value as $id => $val) {
                        if (isset($mytypes[$id])) {
                            $type = $mytypes[$id]['label'];
                        } else {
                            $type = xarML('type #(1)', $id);
                        }
                        $typelist[$id] = $type;
                    }
                }
            }
        }

        if (empty($moduleid) || $moduleid == 'All' || !is_numeric($moduleid)) {
            $moduleid = 0;
        }
        if (empty($itemtype) || $itemtype == 'All' || !is_numeric($itemtype)) {
            $itemtype = 0;
        }
        if (empty($itemid) || $itemid == 'All' || !is_numeric($itemid)) {
            $itemid = 0;
        }
        /* FIXME:  this code already appears further up
        // TODO: figure out how to handle more than 1 category in instances
            if (empty($cid) || $cid == 'All' || !is_numeric($cid)) {
                $cid = 0;
            }
            if (empty($cid) && isset($cids) && is_array($cids)) {
                foreach ($cids as $catid) {
                    if (!empty($catid) && is_numeric($catid)) {
                        $cid = $catid;
                        // bail out for now
                        break;
                    }
                }
            }
        */

        // define the new instance
        $newinstance = [];
        $newinstance[] = empty($moduleid) ? 'All' : $moduleid;
        $newinstance[] = empty($itemtype) ? 'All' : $itemtype;
        $newinstance[] = empty($itemid) ? 'All' : $itemid;
        $newinstance[] = empty($cid) ? 'All' : $cid;

        if (!empty($apply)) {
            // create/update the privilege
            $pid = xarPrivileges::external($extpid, $extname, $extrealm, $extmodule, $extcomponent, $newinstance, $extlevel);
            if (empty($pid)) {
                return; // throw back
            }

            // redirect to the privilege
            xarController::redirect(xarController::URL(
                'privileges',
                'admin',
                'modifyprivilege',
                ['pid' => $pid]
            ), null, $this->getContext());
            return true;
        }

        if (!empty($moduleid)) {
            $numitems = $userapi->countitems(['modid' => $moduleid,
                'itemtype' => $itemtype,
                'cids'  => (empty($cid) ? null : [$cid]),
            ]);
        } else {
            $numitems = xarML('probably');
        }

        $data = [
            'cid'          => $cid,
            'moduleid'     => $moduleid,
            'itemtype'     => $itemtype,
            'itemid'       => $itemid,
            'modlist'      => $modlist,
            'typelist'     => $typelist,
            'numitems'     => $numitems,
            'extpid'       => $extpid,
            'extname'      => $extname,
            'extrealm'     => $extrealm,
            'extmodule'    => $extmodule,
            'extcomponent' => $extcomponent,
            'extlevel'     => $extlevel,
            'extinstance'  => xarVar::prepForDisplay(join(':', $newinstance)),
        ];

        $catlist = [];
        if (!empty($moduleid)) {
            $modinfo = xarMod::getInfo($moduleid);
            $modname = $modinfo['name'];
            sys::import('modules.categories.class.worker');
            $worker = new CategoryWorker();
            if (!empty($itemtype)) {
                $basecats = $worker->getcatbases(
                    ['module'    => 'articles',
                        'itemtype' => $pubid]
                );
                foreach ($basecats as $catid) {
                    $catlist[$catid['cid']] = 1;
                }
            } else {
                $basecats = $worker->getcatbases(
                    ['module'    => 'articles']
                );
                foreach ($basecats as $catid) {
                    $catlist[$catid['cid']] = 1;
                }
            }
        } else {
            // something with categories
        }

        $seencid = [];
        if (!empty($cid)) {
            $seencid[$cid] = 1;
            /*
                    $data['catinfo'] = $userapi->getcatinfo(array('cid' => $cid));
            */
        }

        $data['cids'] = $cids;
        $data['refreshlabel'] = xarML('Refresh');
        $data['applylabel'] = xarML('Finish and Apply to Privilege');

        return $data;
    }
}
