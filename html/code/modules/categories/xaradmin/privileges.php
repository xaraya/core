<?php
/**
 * Categories Module
 *
 * @package modules\categories
 * @subpackage categories
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/147.html
 *
 */

/**
 * 
 */

/**
 * Manage definition of instances for privileges (unfinished)
 * 
 * @param array $args Parameter data array
 * @return array|null Return display data array on success, null on failure.
 */
function categories_admin_privileges($args)
{
    // Security Check
    if (!xarSecurity::check('AdminCategories')) return;

    extract($args);

    // fixed params
    if (!xarVar::fetch('cid',          'isset', $cid,          NULL, xarVar::DONT_SET)) {return;}
    if (!xarVar::fetch('moduleid',     'isset', $moduleid,     NULL, xarVar::DONT_SET)) {return;}
    if (!xarVar::fetch('itemtype',     'isset', $itemtype,     NULL, xarVar::DONT_SET)) {return;}
    if (!xarVar::fetch('itemid',       'isset', $itemid,       NULL, xarVar::DONT_SET)) {return;}
    if (!xarVar::fetch('apply',        'isset', $apply,        NULL, xarVar::DONT_SET)) {return;}
    if (!xarVar::fetch('extpid',       'isset', $extpid,       NULL, xarVar::DONT_SET)) {return;}
    if (!xarVar::fetch('extname',      'isset', $extname,      NULL, xarVar::DONT_SET)) {return;}
    if (!xarVar::fetch('extrealm',     'isset', $extrealm,     NULL, xarVar::DONT_SET)) {return;}
    if (!xarVar::fetch('extmodule',    'isset', $extmodule,    NULL, xarVar::DONT_SET)) {return;}
    if (!xarVar::fetch('extcomponent', 'isset', $extcomponent, NULL, xarVar::DONT_SET)) {return;}
    if (!xarVar::fetch('extinstance',  'isset', $extinstance,  NULL, xarVar::DONT_SET)) {return;}
    if (!xarVar::fetch('extlevel',     'isset', $extlevel,     NULL, xarVar::DONT_SET)) {return;}

    sys::import('modules.dynamicdata.class.properties.master');
    $categories = DataPropertyMaster::getProperty(array('name' => 'categories'));
    $cids = $categories->returnInput('privcategories');

    // 'Category' component = All:cid (catname is unused)
    if (!empty($extcomponent) && $extcomponent == 'Category') {

        // check the current instance
        if (!empty($extinstance)) {
            $parts = explode(':',$extinstance);
            if (count($parts) > 0 && !empty($parts[0])) $catname = $parts[0];
            if (count($parts) > 1 && !empty($parts[1])) $cid = $parts[1];
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
        $newinstance = array();
        if (empty($cid)) {
            $newinstance[] = 'All';
            $newinstance[] = 'All';
        } else {
            $catinfo = xarMod::apiFunc('categories','user','getcatinfo',
                                     array('cid' => $cid));
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
            $pid = xarPrivileges::external($extpid,$extname,$extrealm,$extmodule,$extcomponent,$newinstance,$extlevel);
            if (empty($pid)) {
                return; // throw back
            }

            // redirect to the privilege
            xarController::redirect(xarController::URL('privileges', 'admin', 'modifyprivilege',
                                          array('pid' => $pid)));
            return true;
        }

        $data = array(
                      'cid'          => $cid,
                      'extpid'       => $extpid,
                      'extname'      => $extname,
                      'extrealm'     => $extrealm,
                      'extmodule'    => $extmodule,
                      'extcomponent' => $extcomponent,
                      'extlevel'     => $extlevel,
                      'extinstance'  => xarVar::prepForDisplay(join(':',$newinstance)),
                     );

        $seencid = array();
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
        $parts = explode(':',$extinstance);
        if (count($parts) > 0 && !empty($parts[0])) $moduleid = $parts[0];
        if (count($parts) > 1 && !empty($parts[1])) $itemtype = $parts[1];
        if (count($parts) > 2 && !empty($parts[2])) $itemid = $parts[2];
        if (count($parts) > 3 && !empty($parts[3])) $cid = $parts[3];
    }

    // Get the list of all modules currently hooked to categories
    $hookedmodlist = xarMod::apiFunc('modules','admin','gethookedmodules',
                                   array('hookModName' => 'categories'));
    if (!isset($hookedmodlist)) {
        $hookedmodlist = array();
    }

    $modlist = array();
    $typelist = array();
    foreach ($hookedmodlist as $modname => $value) {
        if (empty($modname)) continue;
        $modid = xarMod::getRegId($modname);
        if (empty($modid)) continue;
        $modinfo = xarMod::getInfo($modid);
        $modlist[$modid] = $modinfo['displayname'];
        if (!empty($moduleid) && $moduleid == $modid) {
            // Get the list of all item types for this module (if any)
            $mytypes = xarMod::apiFunc($modname,'user','getitemtypes',
                                     // don't throw an exception if this function doesn't exist
                                     array(), 0);
            if (empty($mytypes)) {
                $mytypes = array();
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
                        $type = xarML('type #(1)',$id);
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
    $newinstance = array();
    $newinstance[] = empty($moduleid) ? 'All' : $moduleid;
    $newinstance[] = empty($itemtype) ? 'All' : $itemtype;
    $newinstance[] = empty($itemid) ? 'All' : $itemid;
    $newinstance[] = empty($cid) ? 'All' : $cid;

    if (!empty($apply)) {
        // create/update the privilege
        $pid = xarPrivileges::external($extpid,$extname,$extrealm,$extmodule,$extcomponent,$newinstance,$extlevel);
        if (empty($pid)) {
            return; // throw back
        }

        // redirect to the privilege
        xarController::redirect(xarController::URL('privileges', 'admin', 'modifyprivilege',
                                      array('pid' => $pid)));
        return true;
    }

    if (!empty($moduleid)) {
        $numitems = xarMod::apiFunc('categories','user','countitems',
                                  array('modid' => $moduleid,
                                        'itemtype' => $itemtype,
                                        'cids'  => (empty($cid) ? null : array($cid))
                                       ));
    } else {
        $numitems = xarML('probably');
    }

    $data = array(
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
                  'extinstance'  => xarVar::prepForDisplay(join(':',$newinstance)),
                 );

    $catlist = array();
    if (!empty($moduleid)) {
        $modinfo = xarMod::getInfo($moduleid);
        $modname = $modinfo['name'];
        sys::import('modules.categories.class.worker');
        $worker = new CategoryWorker();
        if (!empty($itemtype)) {
            $basecats = $worker->getcatbases(
                                  array('module'    => 'articles',
                                        'itemtype' => $pubid));
            foreach ($basecats as $catid) {
                $catlist[$catid['cid']] = 1;
            }
        } else {
            $basecats = $worker->getcatbases(
                                  array('module'    => 'articles'));
            foreach ($basecats as $catid) {
                $catlist[$catid['cid']] = 1;
            }
        }
    } else {
        // something with categories
    }

    $seencid = array();
    if (!empty($cid)) {
        $seencid[$cid] = 1;
/*
        $data['catinfo'] = xarMod::apiFunc('categories',
                                         'user',
                                         'getcatinfo',
                                         array('cid' => $cid));
*/
    }

    $data['cids'] = $cids;
    $data['refreshlabel'] = xarML('Refresh');
    $data['applylabel'] = xarML('Finish and Apply to Privilege');

    return $data;
}

?>
