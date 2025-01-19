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

use Xaraya\Modules\MethodClass;
use Xaraya\DataObject\AdminGui;
use DataPropertyMaster;
use Exception;
use xarController;
use xarMod;
use xarModUserVars;
use xarModVars;
use xarSec;
use xarSecurity;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * dynamicdata admin migrate function
 * @extends MethodClass<AdminGui>
 */
class MigrateMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * migrate module items
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!xarSecurity::check('AdminDynamicData')) {
            return;
        }

        extract($args);

        // the actual from-to mapping
        if (!$this->var()->fetch('from', 'isset', $from, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('to', 'isset', $to, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('fieldmap', 'isset', $fieldmap, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('hookmap', 'isset', $hookmap, null, xarVar::DONT_SET)) {
            return;
        }

        // support for the Back and Finish buttons
        if (!$this->var()->fetch('step', 'int', $step, 0, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('back', 'str', $back, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('test', 'str', $test, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('confirm', 'str', $confirm, null, xarVar::DONT_SET)) {
            return;
        }

        // support for loading/saving mappings
        if (!$this->var()->fetch('load', 'str', $load, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('save', 'str', $save, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('map', 'str', $map, null, xarVar::DONT_SET)) {
            return;
        }

        if (!xarSecurity::check('AdminDynamicData')) {
            return;
        }

        // retrieve past steps and recover if necessary
        if (!xarModVars::get('dynamicdata', 'migratesteps')) {
            xarModVars::set('dynamicdata', 'migratesteps', serialize([]));
        }
        if (empty($from) && empty($to)) {
            $steps = [];
        } else {
            $steps = xarModUserVars::get('dynamicdata', 'migratesteps');
            if (!empty($steps)) {
                $steps = unserialize($steps);
            } else {
                $steps = [];
            }
        }
        if (!empty($back)) {
            $step--;
            if (!empty($step) && !empty($steps[$step])) {
                // recover $from, $to, $fieldmap and $hookmap from previous step
                extract($steps[$step]);
            }
        } else {
            $step++;
        }

        // retrieve existing mappings and recover if necessary
        $maps = xarModVars::get('dynamicdata', 'migratemaps');
        if (empty($maps)) {
            xarModVars::set('dynamicdata', 'migratemaps', serialize([]));
            $maps = [];
        } else {
            $maps = unserialize($maps);
        }
        if (!empty($load) && !empty($map) && !empty($maps[$map])) {
            // recover $from, $to, $fieldmap and $hookmap from existing map
            extract($maps[$map]);
            // reset itemid and steps
            $from['itemid'] = null;
            $steps = [];
            $step = 1;
        }

        // Get the list of all modules
        $modlist = xarMod::apiFunc('modules', 'admin', 'getlist');

        // Get the list of all hook modules, and the current hooks enabled for all modules
        $hooklist = xarMod::apiFunc('modules', 'admin', 'gethooklist');

        $data = [];

        $data['modulelist'] = [];
        foreach ($modlist as $modinfo) {
            $data['modulelist'][$modinfo['regid']] = $modinfo['displayname'];
        }

        // list of modules supported by the migration process (for now)
        $modsupported = ['articles','dynamicdata','xarbb','xarpages'];

        $data['modulesupported'] = [];
        foreach ($modsupported as $modname) {
            $data['modulesupported'][] = xarMod::getRegID($modname);
        }

        // list of hooks supported by the migration process (for now)
        $data['hooksupported'] = ['categories','changelog','comments','dynamicdata','hitcount','keywords','polls','ratings','uploads','xlink'];

        $data['from'] = [];
        if (!empty($from) && is_array($from)) {
            if (!empty($from['objectid'])) {
                // TODO ?
            } elseif (!empty($from['table'])) {
                // TODO ?
            } elseif (!empty($from['module'])) {
                // we have a from module
                $data['from']['module'] = $from['module'];
                $modinfo = xarMod::getInfo($from['module']);
                // Get the list of all item types for this module (if any)
                try {
                    $itemtypes = xarMod::apiFunc($modinfo['name'], 'user', 'getitemtypes');
                } catch (Exception $e) {
                    $itemtypes = [];
                }
                if (!empty($itemtypes)) {
                    $data['fromitemtypes'] = $itemtypes;
                } else {
                    $data['fromitemtypes'] = [];
                }

                if (isset($from['itemtype'])) {
                    // we have a from itemtype
                    $data['from']['itemtype'] = $from['itemtype'];
                    if (!empty($from['itemid'])) {
                        // we have a from itemid
                        if (is_string($from['itemid'])) {
                            $from['itemid'] = explode(',', $from['itemid']);
                        }
                        $data['from']['itemid'] = join(',', $from['itemid']);
                    }

                    // get the list of items for this module+itemtype
                    if (empty($from['itemid'])) {
                        $items = xarMod::apiFunc(
                            $modinfo['name'],
                            'user',
                            'getitemlinks',
                            ['itemtype' => $from['itemtype'],
                                'itemids'  => null]
                        );
                    } else {
                        $items = xarMod::apiFunc(
                            $modinfo['name'],
                            'user',
                            'getitemlinks',
                            ['itemtype' => $from['itemtype'],
                                'itemids'  => $from['itemid']]
                        );
                    }
                    if (!empty($items)) {
                        $data['fromitems'] = $items;
                    } else {
                        $data['fromitems'] = [];
                    }

                    if (!empty($itemtypes[$from['itemtype']])) {
                        $mapfrom = $itemtypes[$from['itemtype']]['label'];
                    }

                    // get the list of fields for this module+itemtype
                    $fields = xarMod::apiFunc(
                        $modinfo['name'],
                        'user',
                        'getitemfields',
                        ['itemtype' => $from['itemtype']]
                    );
                    if (!empty($fields)) {
                        $data['fromfieldlist'] = $fields;
                    } else {
                        $data['fromfieldlist'] = [];
                    }

                    // get the list of hooks for this module+itemtype
                    $data['fromhooklist'] = [];
                    $modname = $modinfo['name'];
                    foreach ($hooklist as $hookmodname => $hooks) {
                        // Fill in the details for the different hooks
                        foreach ($hooks as $hook => $modules) {
                            if (empty($modules[$modname])) {
                                continue;
                            }
                            foreach ($modules[$modname] as $itemtype => $val) {
                                if (empty($itemtype)) {
                                    // the module is hooked for all itemtypes
                                    $data['fromhooklist'][$hookmodname] = 1;
                                    break;
                                } elseif ($itemtype == $data['from']['itemtype']) {
                                    // the module is hooked for this particular itemtype
                                    $data['fromhooklist'][$hookmodname] = 1;
                                    break;
                                }
                            }
                        }
                    }

                    // add DD properties to field list
                    if (!empty($data['fromhooklist']['dynamicdata'])) {
                        $props = xarMod::apiFunc(
                            'dynamicdata',
                            'user',
                            'getprop',
                            ['module_id'    => $data['from']['module'],
                                'itemtype' => $data['from']['itemtype']]
                        );
                        $proptypes = DataPropertyMaster::getPropertyTypes();
                        foreach ($props as $name => $info) {
                            if (empty($info['label'])) {
                                continue;
                            }
                            if (!empty($proptypes[$info['type']])) {
                                $type = $proptypes[$info['type']]['name'];
                            } else {
                                $type = $info['type'];
                            }
                            // CHECKME: use dd_NN as field name here ?
                            $label = '(dd_' . $info['id'] . ') ' . $info['label'];
                            $data['fromfieldlist'][$name] = [
                                'name'  => $name,
                                'label' => $label,
                                'type'  => $type,
                            ];
                        }
                    }
                }
            }
        }

        $data['to'] = [];
        if (!empty($to) && is_array($to)) {
            if (!empty($to['objectid'])) {
                // TODO ?
            } elseif (!empty($to['table'])) {
                // TODO ?
            } elseif (!empty($to['module'])) {
                // we have a to module
                $data['to']['module'] = $to['module'];
                $modinfo = xarMod::getInfo($to['module']);
                // Get the list of all item types for this module (if any)
                try {
                    $itemtypes = xarMod::apiFunc($modinfo['name'], 'user', 'getitemtypes');
                } catch (Exception $e) {
                    $itemtypes = [];
                }
                if (!empty($itemtypes)) {
                    $data['toitemtypes'] = $itemtypes;
                } else {
                    $data['toitemtypes'] = [];
                }

                if (isset($to['itemtype'])) {
                    // we have a to itemtype
                    $data['to']['itemtype'] = $to['itemtype'];
                    if (!empty($to['itemid'])) {
                        // we have a to itemid (= checkbox to preserve the itemid or not here)
                        $data['to']['itemid'] = $to['itemid'];
                    }
                    if (!empty($itemtypes[$to['itemtype']])) {
                        $mapto = $itemtypes[$to['itemtype']]['label'];
                    }

                    // get the list of fields for this module+itemtype
                    $fields = xarMod::apiFunc(
                        $modinfo['name'],
                        'user',
                        'getitemfields',
                        ['itemtype' => $to['itemtype']]
                    );
                    if (!empty($fields)) {
                        $data['tofieldlist'] = $fields;
                    } else {
                        $data['tofieldlist'] = [];
                    }

                    // get the list of hooks enabled for this module+itemtype
                    $data['tohooklist'] = [];
                    $modname = $modinfo['name'];
                    foreach ($hooklist as $hookmodname => $hooks) {
                        // Fill in the details for the different hooks
                        foreach ($hooks as $hook => $modules) {
                            if (empty($modules[$modname])) {
                                continue;
                            }
                            foreach ($modules[$modname] as $itemtype => $val) {
                                if (empty($itemtype)) {
                                    // the module is hooked for all itemtypes
                                    $data['tohooklist'][$hookmodname] = 1;
                                    break;
                                } elseif ($itemtype == $data['to']['itemtype']) {
                                    // the module is hooked for this particular itemtype
                                    $data['tohooklist'][$hookmodname] = 1;
                                    break;
                                }
                            }
                        }
                    }

                    // add DD properties to field list
                    if (!empty($data['tohooklist']['dynamicdata'])) {
                        $props = xarMod::apiFunc(
                            'dynamicdata',
                            'user',
                            'getprop',
                            ['module_id'    => $data['to']['module'],
                                'itemtype' => $data['to']['itemtype']]
                        );
                        $proptypes = DataPropertyMaster::getPropertyTypes();
                        foreach ($props as $name => $info) {
                            if (empty($info['label'])) {
                                continue;
                            }
                            if (!empty($proptypes[$info['type']])) {
                                $type = $proptypes[$info['type']]['name'];
                            } else {
                                $type = $info['type'];
                            }
                            // CHECKME: use dd_NN as field name here ?
                            $label = '(dd_' . $info['id'] . ') ' . $info['label'];
                            $data['tofieldlist'][$name] = [
                                'name'  => $name,
                                'label' => $label,
                                'type'  => $type,
                            ];
                        }
                    }
                }
            }
        }

        // check the field mapping
        $data['fieldmap'] = [];
        if (!empty($fieldmap) && !empty($data['fromfieldlist']) && !empty($data['tofieldlist'])) {
            foreach ($fieldmap as $fromfield => $tofield) {
                if (empty($fromfield)) {
                    continue;
                }
                if (!empty($tofield) && !empty($data['tofieldlist'][$tofield])) {
                    $data['fieldmap'][$fromfield] = $tofield;
                } else {
                    $data['fieldmap'][$fromfield] = '';
                }
            }
        }

        // check the hook mapping
        $data['hookmap'] = [];
        if (!empty($hookmap) && !empty($data['fromhooklist']) && !empty($data['tohooklist'])) {
            foreach ($hookmap as $fromhook => $tohook) {
                if (empty($fromhook)) {
                    continue;
                }
                if (!empty($tohook) && !empty($data['tohooklist'][$tohook])) {
                    $data['hookmap'][$fromhook] = $tohook;
                } else {
                    $data['hookmap'][$fromhook] = '';
                }
            }
        }

        // preserve current step
        $steps[$step] = ['from' => $data['from'], 'to' => $data['to'],
            'fieldmap' => $data['fieldmap'], 'hookmap' => $data['hookmap']];
        xarModUserVars::set('dynamicdata', 'migratesteps', serialize($steps));
        $data['step'] = $step;

        // see if we have everything we need to finish if necessary
        if (!empty($from['module']) && !empty($from['itemtype']) && !empty($from['itemid']) &&
            !empty($to['module']) && !empty($to['itemtype'])) {
            $data['check'] = 1;
        } else {
            $data['check'] = 0;
        }

        // migrate item(s)
        if ((!empty($test) || !empty($confirm)) && !empty($data['check'])) {
            if (!$this->sec()->confirmAuthKey()) {
                return $this->ctl()->badRequest('bad_author');
            }

            if (!empty($test)) {
                $data['debug'] = $this->ml('Test Results') . "\n";
            }
            $result = xarMod::apiFunc(
                'dynamicdata',
                'admin',
                'migrate',
                $data
            );
            if (!$result) {
                return;
            }
            if (!empty($test)) {
                // put test results in debug string
                $data['debug'] = $this->var()->prep($result);
            } elseif (!empty($confirm)) {
                // return and load the same map again
                $url = xarController::URL(
                    'dynamicdata',
                    'admin',
                    'migrate',
                    ['load' => 1, 'map' => $map]
                );
                $this->ctl()->redirect($url);
                return true;
            }
        }

        // save current map
        if (!empty($save)) {
            if (!$this->var()->fetch('newmap', 'str', $newmap, null, xarVar::DONT_SET)) {
                return;
            }
            if (!empty($newmap)) {
                $map = $newmap;
            }
            if (!empty($map)) {
                $maps[$map] = [
                    'from' => $data['from'],
                    'to' => $data['to'],
                    'fieldmap' => $data['fieldmap'],
                    'hookmap' => $data['hookmap'],
                ];
                xarModVars::set('dynamicdata', 'migratemaps', serialize($maps));
            }
        }

        $data['maplist'] = array_keys($maps);
        $data['map'] = $map;
        if (empty($map) && !empty($mapfrom) && !empty($mapto)) {
            $data['newmap'] = $mapfrom . ' - ' . $mapto;
        } else {
            $data['newmap'] = '';
        }
        $data['mapfrom'] = !empty($mapfrom) ? $mapfrom : '';
        $data['mapto'] = !empty($mapto) ? $mapto : '';

        return $data;
    }
}
