<?php

/**
 * @package modules\modules
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Modules\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Modules\AdminApi;
use EmptyParameterException;
use ModuleNotFoundException;

/**
 * modules adminapi updatehooks function
 * @extends MethodClass<AdminApi>
 */
class UpdatehooksMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Update hooks for a particular hook module
     * @author Xaraya Development Team
     * @param array<string,mixed> $args array of optional parameters<br/>
     * integer  $args['regid'] the id number of the hook module
     * @return bool|void true on success, false on failure
     * @see AdminApi::updatehooks()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Get arguments from argument array
        extract($args);

        // Argument check
        if (!isset($regid)) {
            throw new EmptyParameterException('regid');
        }

        // Security Check
        if (!$this->sec()->check('ManageModules', 0, 'All', "All:All:$regid")) {
            return;
        }

        // Get module name
        $modinfo = $this->mod()->getInfo($regid);
        if (empty($modinfo['name'])) {
            throw new ModuleNotFoundException($regid, 'Invalid module name found while updating hooks for module with regid #(1)');
        }
        $curhook = $modinfo['name'];
        // new way of handling this (sane way)
        if (!empty($subjects) && is_array($subjects)) {
            foreach ($subjects as $module => $values) {

                // remove current assignments
                $this->hooked()->detach($curhook, $module, -1, -1);
                switch ($values['hookstate']) {
                    case 0:
                        // hooked to none
                        break;
                    case 1:
                        // hooked to all scopes, all items
                        $this->hooked()->attach($curhook, $module, 0, 0);
                        break;
                    case 2:
                        // hooked to some scopes, all items
                        // see which scopes
                        if (!empty($values['itemtypes'][0])) {
                            foreach ($values['itemtypes'][0] as $scope => $setting) {
                                if (empty($scope)) {
                                    continue;
                                }
                                if (!empty($setting)) {
                                    $this->hooked()->attach($curhook, $module, 0, $scope);
                                }
                            }
                        }

                        break;
                    case 3:
                        // hooked to some scopes, some items
                        // see which items
                        if (!empty($values['itemtypes'])) {
                            foreach ($values['itemtypes'] as $itemtype => $typeinfo) {
                                if (empty($itemtype)) {
                                    continue;
                                }
                                switch ($typeinfo['scopes'][0]) {
                                    case 0:
                                        // none
                                        break;
                                    case 1:
                                        // all scopes, this itemtype
                                        $this->hooked()->attach($curhook, $module, $itemtype, 0);
                                        break;
                                    case 2:
                                        // some scopes, this itemtype
                                        // see which scopes
                                        foreach ($typeinfo['scopes'] as $scope => $setting) {
                                            if (empty($scope)) {
                                                continue;
                                            }
                                            if (!empty($setting)) {
                                                $this->hooked()->attach($curhook, $module, $itemtype, $scope);
                                            }
                                        }
                                        break;
                                }
                            }
                        }

                        break;
                }

            }
        } else {
            // Legacy support, deprecated
            // get the list of all (active) modules
            $modList = $adminapi->getlist();
            // see for which one(s) we need to enable this hook
            foreach ($modList as $mod) {
                // CHECKME: don't allow hooking to yourself !?
                // <chris> hooking to self is allowed, eg, roles usermenu > roles
                // We may however wish to consider a way of allowing modules to over-ride
                // this behaviour, eg, dd really shouldn't be hooked to dd
                //if ($mod['systemid'] == $modinfo['systemid']) continue;
                // Get selected value of hook (which is an array of all the itemtypes selected)
                // hooked_$mod['name'][0] contains the global setting ( 0 -> not, 1 -> all, 2 -> some)
                $this->var()->update("hooked_" . $mod['name'], $ishooked, 'isset', '');
                // remove current assignments
                $this->hooked()->detach($curhook, $mod['name'], -1);
                // No setting or explicit NOT, skip it (note: empty shouldn't occur anymore
                if (!empty($ishooked) && $ishooked[0] != 0) {
                    if ($ishooked[0] == 1) {
                        // hooked to all itemtypes
                        $this->hooked()->attach($curhook, $mod['name'], 0, 0);
                    } elseif ($ishooked[0] == 2) {
                        // hooked to some itemtypes
                        foreach (array_keys($ishooked) as $itemtype) {
                            // skip itemtype 0
                            if ($itemtype == 0) {
                                continue;
                            }
                            $this->hooked()->attach($curhook, $mod['name'], $itemtype, 0);
                        }
                    }
                }
            }
        }
        return true;

    }
}
