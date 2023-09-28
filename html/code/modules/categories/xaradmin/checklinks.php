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
 * Check category links for orphans
 * 
 * @return array<mixed>|bool|string|void Returns data array on success, false|null on failure
 */
function categories_admin_checklinks()
{
    // Security Check
    if (!xarSecurity::check('AdminCategories')) return;

    if(!xarVar::fetch('modid',    'isset',  $modid,    NULL, xarVar::DONT_SET)) {return;}
    if(!xarVar::fetch('itemtype', 'isset',  $itemtype, NULL, xarVar::DONT_SET)) {return;}

    $data = array();

    $modlist = xarMod::apiFunc('categories','user','getmodules');

    if (empty($modid)) {
        $data['moditems'] = array();
        $data['numitems'] = 0;
        $data['numlinks'] = 0;
        foreach ($modlist as $modid => $itemtypes) {
            $modinfo = xarMod::getInfo($modid);
            // Get the list of all item types for this module (if any)
            try {
                $mytypes = xarMod::apiFunc($modinfo['name'],'user','getitemtypes',
                // don't throw an exception if this function doesn't exist
                array());
            } catch (Exception $e) {
                $mytypes = [];
            }
            foreach ($itemtypes as $itemtype => $stats) {
                $moditem = array();
                $moditem['numitems'] = $stats['items'];
                $moditem['numcats'] = $stats['cats'];
                $moditem['numlinks'] = $stats['links'];
                if ($itemtype == 0) {
                    $moditem['name'] = ucwords($modinfo['displayname']);
                //    $moditem['link'] = xarController::URL($modinfo['name'],'user','main');
                } else {
                    if (isset($mytypes) && !empty($mytypes[$itemtype])) {
                        $moditem['name'] = ucwords($modinfo['displayname']) . ' ' . $itemtype . ' - ' . $mytypes[$itemtype]['label'];
                    //    $moditem['link'] = $mytypes[$itemtype]['url'];
                    } else {
                        $moditem['name'] = ucwords($modinfo['displayname']) . ' ' . $itemtype;
                    //    $moditem['link'] = xarController::URL($modinfo['name'],'user','view',array('itemtype' => $itemtype));
                    }
                }
                $moditem['link'] = xarController::URL('categories','admin','checklinks',
                                             array('modid' => $modid,
                                                   'itemtype' => empty($itemtype) ? null : $itemtype));
                $data['moditems'][] = $moditem;
                $data['numitems'] += $moditem['numitems'];
                $data['numlinks'] += $moditem['numlinks'];
            }
        }
    } else {
        $modinfo = xarMod::getInfo($modid);
        $data['module'] = $modinfo['name'];
        if (empty($itemtype)) {
            $data['itemtype'] = 0;
            $data['modname'] = ucwords($modinfo['displayname']);
            $itemtype = null;
            if (isset($modlist[$modid][0])) {
                $stats = $modlist[$modid][0];
            }
        } else {
            $data['itemtype'] = $itemtype;
            // Get the list of all item types for this module (if any)
            try {
                $mytypes = xarMod::apiFunc($modinfo['name'],'user','getitemtypes',
                // don't throw an exception if this function doesn't exist
                array());
            } catch (Exception $e) {
                $mytypes = [];
            }
            if (isset($mytypes) && !empty($mytypes[$itemtype])) {
                $data['modname'] = ucwords($modinfo['displayname']) . ' ' . $itemtype . ' - ' . $mytypes[$itemtype]['label'];
            //    $data['modlink'] = $mytypes[$itemtype]['url'];
            } else {
                $data['modname'] = ucwords($modinfo['displayname']) . ' ' . $itemtype;
            //    $data['modlink'] = xarController::URL($modinfo['name'],'user','view',array('itemtype' => $itemtype));
            }
            if (isset($modlist[$modid][$itemtype])) {
                $stats = $modlist[$modid][$itemtype];
            }
        }
        if (isset($stats)) {
            $data['numitems'] = $stats['items'];
            $data['numlinks'] = $stats['links'];
        } else {
            $data['numitems'] = 0;
            $data['numlinks'] = '';
        }
        $numstats = xarModVars::get('categories','numstats');
        if (empty($numstats)) {
            $numstats = 100;
        }
        $data['pager'] = '';
        $data['modid'] = $modid;
        $getitems = xarMod::apiFunc('categories','user','getorphanlinks',
                                  array('modid' => $modid,
                                        'itemtype' => $itemtype));
        $data['numorphans'] = count($getitems);
        $showtitle = xarModVars::get('categories','showtitle');
        if (!empty($getitems) && !empty($showtitle)) {
            $itemids = array_keys($getitems);
            try {
                $itemlinks = xarMod::apiFunc($modinfo['name'],'user','getitemlinks',
                                            array('itemtype' => $itemtype,
                                                    'itemids' => $itemids)); // don't throw an exception here
            } catch (Exception $e) {
                $itemlinks = [];
            }
        } else {
           $itemlinks = array();
        }
        $seencid = array();
        $data['moditems'] = array();
        foreach ($getitems as $itemid => $cids) {
            $data['moditems'][$itemid] = array();
            $data['moditems'][$itemid]['numlinks'] = count($cids);
            $data['moditems'][$itemid]['cids'] = $cids;
            foreach ($cids as $cid) {
                $seencid[$cid] = 1;
            }
            if (isset($itemlinks[$itemid])) {
                $data['moditems'][$itemid]['link'] = $itemlinks[$itemid]['url'];
                $data['moditems'][$itemid]['title'] = $itemlinks[$itemid]['label'];
            }
        }
        unset($getitems);
        unset($itemlinks);
        if (!empty($seencid)) {
            $data['catinfo'] = xarMod::apiFunc('categories','user','getcatinfo',
                                             array('cids' => array_keys($seencid)));
        } else {
            $data['catinfo'] = array();
        }

        if(!xarVar::fetch('confirm',  'str:1:', $confirm,    '', xarVar::NOT_REQUIRED)) return;
        if (!empty($seencid) && !empty($confirm)) {
            if (!xarSec::confirmAuthKey()) {
                return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
            }        
            if (!xarMod::apiFunc('categories','admin','unlinkcids',
                               array('modid' => $modid,
                                     'itemtype' => $itemtype,
                                     'cids' => array_keys($seencid)))) {
                return;
            }
            xarController::redirect(xarController::URL('categories', 'admin', 'checklinks'));
            return true;
        }

        // Generate a one-time authorisation code for this operation
        $data['authid'] = xarSec::genAuthKey();
    }

    return $data;
}
