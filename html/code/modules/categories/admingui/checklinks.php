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
use Xaraya\Modules\Categories\AdminApi;
use Exception;
use xarController;
use xarMod;
use xarModVars;
use xarSec;
use xarSecurity;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * categories admin checklinks function
 * @extends MethodClass<AdminGui>
 */
class ChecklinksMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Check category links for orphans
     * @return array|bool|string|void Returns data array on success, false|null on failure
     * @see AdminGui::checklinks()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security Check
        if (!xarSecurity::check('AdminCategories')) {
            return;
        }

        $this->var()->check('modid', $modid);
        $this->var()->check('itemtype', $itemtype);

        $data = [];

        $modlist = $userapi->getmodules();

        if (empty($modid)) {
            $data['moditems'] = [];
            $data['numitems'] = 0;
            $data['numlinks'] = 0;
            foreach ($modlist as $modid => $itemtypes) {
                $modinfo = xarMod::getInfo($modid);
                // Get the list of all item types for this module (if any)
                try {
                    $mytypes = xarMod::apiFunc($modinfo['name'], 'user', 'getitemtypes');
                } catch (Exception $e) {
                    $mytypes = [];
                }
                foreach ($itemtypes as $itemtype => $stats) {
                    $moditem = [];
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
                    $moditem['link'] = xarController::URL(
                        'categories',
                        'admin',
                        'checklinks',
                        ['modid' => $modid,
                            'itemtype' => empty($itemtype) ? null : $itemtype]
                    );
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
                    $mytypes = xarMod::apiFunc($modinfo['name'], 'user', 'getitemtypes');
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
            $numstats = xarModVars::get('categories', 'numstats');
            if (empty($numstats)) {
                $numstats = 100;
            }
            $data['pager'] = '';
            $data['modid'] = $modid;
            $getitems = $userapi->getorphanlinks(['modid' => $modid,
                'itemtype' => $itemtype]);
            $data['numorphans'] = count($getitems);
            $showtitle = xarModVars::get('categories', 'showtitle');
            if (!empty($getitems) && !empty($showtitle)) {
                $itemids = array_keys($getitems);
                try {
                    $itemlinks = xarMod::apiFunc(
                        $modinfo['name'],
                        'user',
                        'getitemlinks',
                        ['itemtype' => $itemtype,
                            'itemids' => $itemids]
                    );
                } catch (Exception $e) {
                    $itemlinks = [];
                }
            } else {
                $itemlinks = [];
            }
            $seencid = [];
            $data['moditems'] = [];
            foreach ($getitems as $itemid => $cids) {
                $data['moditems'][$itemid] = [];
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
                $data['catinfo'] = $userapi->getcatinfo(['cids' => array_keys($seencid)]);
            } else {
                $data['catinfo'] = [];
            }

            $this->var()->find('confirm', $confirm, 'str:1:', '');
            if (!empty($seencid) && !empty($confirm)) {
                if (!xarSec::confirmAuthKey()) {
                    return xarController::badRequest('bad_author', $this->getContext());
                }
                if (!$adminapi->unlinkcids(['modid' => $modid,
                    'itemtype' => $itemtype,
                    'cids' => array_keys($seencid)])) {
                    return;
                }
                xarController::redirect(xarController::URL('categories', 'admin', 'checklinks'), null, $this->getContext());
                return true;
            }

            // Generate a one-time authorisation code for this operation
            $data['authid'] = xarSec::genAuthKey();
        }

        return $data;
    }
}
