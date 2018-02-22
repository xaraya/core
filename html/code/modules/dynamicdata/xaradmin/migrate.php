<?php
/**
 * migrate module items
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * migrate module items
 */
function dynamicdata_admin_migrate(Array $args=array())
{
    // Security
    if (!xarSecurityCheck('AdminDynamicData')) return;

    extract($args);

    // the actual from-to mapping
    if(!xarVarFetch('from',     'isset', $from,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('to',       'isset', $to,       NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('fieldmap', 'isset', $fieldmap, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('hookmap',  'isset', $hookmap,  NULL, XARVAR_DONT_SET)) {return;}

    // support for the Back and Finish buttons
    if(!xarVarFetch('step',     'int',   $step,     0,    XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('back',     'str',   $back,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('test',     'str',   $test,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('confirm',  'str',   $confirm,  NULL, XARVAR_DONT_SET)) {return;}

    // support for loading/saving mappings
    if(!xarVarFetch('load',     'str',   $load,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('save',     'str',   $save,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('map',      'str',   $map,      NULL, XARVAR_DONT_SET)) {return;}

    if(!xarSecurityCheck('AdminDynamicData')) return;

    // retrieve past steps and recover if necessary
    if (!xarModVars::get('dynamicdata','migratesteps')) {
        xarModVars::set('dynamicdata','migratesteps',serialize(array()));
    }
    if (empty($from) && empty($to)) {
        $steps = array();
    } else {
        $steps = xarModUserVars::get('dynamicdata','migratesteps');
        if (!empty($steps)) {
            $steps = unserialize($steps);
        } else {
            $steps = array();
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
    $maps = xarModVars::get('dynamicdata','migratemaps');
    if (empty($maps)) {
        xarModVars::set('dynamicdata','migratemaps',serialize(array()));
        $maps = array();
    } else {
        $maps = unserialize($maps);
    }
    if (!empty($load) && !empty($map) && !empty($maps[$map])) {
        // recover $from, $to, $fieldmap and $hookmap from existing map
        extract($maps[$map]);
        // reset itemid and steps
        $from['itemid'] = null;
        $steps = array();
        $step = 1;
    }

    // Get the list of all modules
    $modlist = xarMod::apiFunc('modules', 'admin', 'getlist');

    // Get the list of all hook modules, and the current hooks enabled for all modules
    $hooklist = xarMod::apiFunc('modules','admin','gethooklist');

    $data = array();

    $data['modulelist'] = array();
    foreach ($modlist as $modinfo) {
        $data['modulelist'][$modinfo['regid']] = $modinfo['displayname'];
    }

    // list of modules supported by the migration process (for now)
    $modsupported = array('articles','dynamicdata','xarbb','xarpages');

    $data['modulesupported'] = array();
    foreach ($modsupported as $modname) {
        $data['modulesupported'][] = xarMod::getRegID($modname);
    }

    // list of hooks supported by the migration process (for now)
    $data['hooksupported'] = array('categories','changelog','comments','dynamicdata','hitcount','keywords','polls','ratings','uploads','xlink');

    $data['from'] = array();
    if (!empty($from) && is_array($from)) {
        if (!empty($from['objectid'])) {
            // TODO ?
        } elseif (!empty($from['table'])) {
            // TODO ?
        } elseif (!empty($from['module'])) {
            // we have a from module
            $data['from']['module'] = $from['module'];
            $modinfo = xarMod::getInfo($from['module']);

            // get the list of itemtypes for this module
            $itemtypes = xarMod::apiFunc($modinfo['name'],'user','getitemtypes',
                                       array(),
                                       0);
            if (!empty($itemtypes)) {
                $data['fromitemtypes'] = $itemtypes;
            } else {
                $data['fromitemtypes'] = array();
            }

            if (isset($from['itemtype'])) {
                // we have a from itemtype
                $data['from']['itemtype'] = $from['itemtype'];
                if (!empty($from['itemid'])) {
                    // we have a from itemid
                    if (is_string($from['itemid'])) {
                        $from['itemid'] = explode(',',$from['itemid']);
                    }
                    $data['from']['itemid'] = join(',',$from['itemid']);
                }

                // get the list of items for this module+itemtype
                if (empty($from['itemid'])) {
                    $items = xarMod::apiFunc($modinfo['name'],'user','getitemlinks',
                                           array('itemtype' => $from['itemtype'],
                                                 'itemids'  => null),
                                           0);
                } else {
                    $items = xarMod::apiFunc($modinfo['name'],'user','getitemlinks',
                                           array('itemtype' => $from['itemtype'],
                                                 'itemids'  => $from['itemid']),
                                           0);
                }
                if (!empty($items)) {
                    $data['fromitems'] = $items;
                } else {
                    $data['fromitems'] = array();
                }

                if (!empty($itemtypes[$from['itemtype']])) {
                    $mapfrom = $itemtypes[$from['itemtype']]['label'];
                }

                // get the list of fields for this module+itemtype
                $fields = xarMod::apiFunc($modinfo['name'],'user','getitemfields',
                                        array('itemtype' => $from['itemtype']),
                                        0);
                if (!empty($fields)) {
                    $data['fromfieldlist'] = $fields;
                } else {
                    $data['fromfieldlist'] = array();
                }

                // get the list of hooks for this module+itemtype
                $data['fromhooklist'] = array();
                $modname = $modinfo['name'];
                foreach ($hooklist as $hookmodname => $hooks) {
                    // Fill in the details for the different hooks
                    foreach ($hooks as $hook => $modules) {
                        if (empty($modules[$modname])) continue;
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
                    $props = xarMod::apiFunc('dynamicdata','user','getprop',
                                           array('module_id'    => $data['from']['module'],
                                                 'itemtype' => $data['from']['itemtype']));
                    $proptypes = DataPropertyMaster::getPropertyTypes();
                    foreach ($props as $name => $info) {
                        if (empty($info['label'])) continue;
                        if (!empty($proptypes[$info['type']])) {
                            $type = $proptypes[$info['type']]['name'];
                        } else {
                            $type = $info['type'];
                        }
                    // CHECKME: use dd_NN as field name here ?
                        $label = '(dd_' . $info['id'] . ') ' . $info['label'];
                        $data['fromfieldlist'][$name] = array('name'  => $name,
                                                              'label' => $label,
                                                              'type'  => $type);
                    }
                }
            }
        }
    }

    $data['to'] = array();
    if (!empty($to) && is_array($to)) {
        if (!empty($to['objectid'])) {
            // TODO ?
        } elseif (!empty($to['table'])) {
            // TODO ?
        } elseif (!empty($to['module'])) {
            // we have a to module
            $data['to']['module'] = $to['module'];
            $modinfo = xarMod::getInfo($to['module']);

            // get the list of itemtypes for this module
            $itemtypes = xarMod::apiFunc($modinfo['name'],'user','getitemtypes',
                                       array(),
                                       0);
            if (!empty($itemtypes)) {
                $data['toitemtypes'] = $itemtypes;
            } else {
                $data['toitemtypes'] = array();
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
                $fields = xarMod::apiFunc($modinfo['name'],'user','getitemfields',
                                        array('itemtype' => $to['itemtype']),
                                        0);
                if (!empty($fields)) {
                    $data['tofieldlist'] = $fields;
                } else {
                    $data['tofieldlist'] = array();
                }

                // get the list of hooks enabled for this module+itemtype
                $data['tohooklist'] = array();
                $modname = $modinfo['name'];
                foreach ($hooklist as $hookmodname => $hooks) {
                    // Fill in the details for the different hooks
                    foreach ($hooks as $hook => $modules) {
                        if (empty($modules[$modname])) continue;
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
                    $props = xarMod::apiFunc('dynamicdata','user','getprop',
                                           array('module_id'    => $data['to']['module'],
                                                 'itemtype' => $data['to']['itemtype']));
                    $proptypes = DataPropertyMaster::getPropertyTypes();
                    foreach ($props as $name => $info) {
                        if (empty($info['label'])) continue;
                        if (!empty($proptypes[$info['type']])) {
                            $type = $proptypes[$info['type']]['name'];
                        } else {
                            $type = $info['type'];
                        }
                    // CHECKME: use dd_NN as field name here ?
                        $label = '(dd_' . $info['id'] . ') ' . $info['label'];
                        $data['tofieldlist'][$name] = array('name'  => $name,
                                                            'label' => $label,
                                                            'type'  => $type);
                    }
                }
            }
        }
    }

    // check the field mapping
    $data['fieldmap'] = array();
    if (!empty($fieldmap) && !empty($data['fromfieldlist']) && !empty($data['tofieldlist'])) {
        foreach ($fieldmap as $fromfield => $tofield) {
            if (empty($fromfield)) continue;
            if (!empty($tofield) && !empty($data['tofieldlist'][$tofield])) {
                $data['fieldmap'][$fromfield] = $tofield;
            } else {
                $data['fieldmap'][$fromfield] = '';
            }
        }
    }

    // check the hook mapping
    $data['hookmap'] = array();
    if (!empty($hookmap) && !empty($data['fromhooklist']) && !empty($data['tohooklist'])) {
        foreach ($hookmap as $fromhook => $tohook) {
            if (empty($fromhook)) continue;
            if (!empty($tohook) && !empty($data['tohooklist'][$tohook])) {
                $data['hookmap'][$fromhook] = $tohook;
            } else {
                $data['hookmap'][$fromhook] = '';
            }
        }
    }

    // preserve current step
    $steps[$step] = array('from' => $data['from'], 'to' => $data['to'],
                          'fieldmap' => $data['fieldmap'], 'hookmap' => $data['hookmap']);
    xarModUserVars::set('dynamicdata','migratesteps',serialize($steps));
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
        if (!xarSecConfirmAuthKey()) {
            return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
        }        

        if (!empty($test)) {
            $data['debug'] = xarML('Test Results') . "\n";
        }
        $result = xarMod::apiFunc('dynamicdata','admin','migrate',
                                $data);
        if (!$result) return;
        if (!empty($test)) {
            // put test results in debug string
            $data['debug'] = xarVarPrepForDisplay($result);
        } elseif (!empty($confirm)) {
            // return and load the same map again
            $url = xarModURL('dynamicdata','admin','migrate',
                             array('load' => 1, 'map' => $map));
            xarController::redirect($url);
            return true;
        }
    }

    // save current map
    if (!empty($save)) {
        if(!xarVarFetch('newmap', 'str', $newmap, NULL, XARVAR_DONT_SET)) {return;}
        if (!empty($newmap)) {
            $map = $newmap;
        }
        if (!empty($map)) {
            $maps[$map] = array('from' => $data['from'], 'to' => $data['to'],
                                'fieldmap' => $data['fieldmap'], 'hookmap' => $data['hookmap']);
            xarModVars::set('dynamicdata','migratemaps',serialize($maps));
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

?>
