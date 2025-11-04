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
use Query;
use ixarMod;
use sys;

sys::import('xaraya.modules.method');

/**
 * modules adminapi getitems function
 * @extends MethodClass<AdminApi>
 */
class GetitemsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     *
     * @package modules\modules
     * @subpackage modules
     * @category Xaraya Web Applications Framework
     * @version 2.4.0
     * @copyright see the html/credits.html file in this release
     * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
     * @link http://xaraya.info/index.php/release/1.html
     * @see AdminApi::getitems()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        // Set some defaults
        if (!isset($state)) {
            $state = ixarMod::STATE_ACTIVE;
        }
        if (!isset($include_core)) {
            $include_core = true;
        }
        if (!isset($sort)) {
            $sort = 'name ASC';
        }

        // Determine the table we are going to use
        $tables = $this->db()->getTables();
        sys::import('xaraya.structures.query');
        $q = new Query('SELECT', $tables['modules']);
        $q->addfields("id, regid, name, directory, version, class, category, state, user_capable, admin_capable");

        if (!empty($regid)) {
            $q->eq('regid', $regid);
        }

        if (!empty($name)) {
            if (is_array($name)) {
                $q->in('name', $name);
            } else {
                $q->eq('name', $name);
            }
        }

        if ($state != ixarMod::STATE_ANY) {
            if ($state != ixarMod::STATE_INSTALLED) {
                $q->eq('state', $state);
            } else {
                $q->ne('state', ixarMod::STATE_UNINITIALISED);
                $q->lt('state', ixarMod::STATE_MISSING_FROM_INACTIVE);
                $q->ne('state', ixarMod::STATE_MISSING_FROM_UNINITIALISED);
            }
        }

        if (!empty($modclass)) {
            $q->eq('class', $modclass);
        }
        if (!empty($category)) {
            $q->eq('category', $category);
        }

        if (!$include_core) {
            $coremods = ['base','roles','privileges','blocks','themes','authsystem','mail','dynamicdata','installer','modules','categories'];
            $q->notin('name', $coremods);
        }

        if (!empty($user_capable)) {
            $q->eq('user_capable', (int) $user_capable);
        }
        if (!empty($admin_capable)) {
            $q->eq('admin_capable', (int) $admin_capable);
        }

        if (!is_array($sort)) {
            $sort = strpos($sort, ',') !== false ? array_map('trim', explode(',', $sort)) : [trim($sort)];
        }
        foreach ($sort as $pairs) {
            [$sortfield, $sortorder] = array_map('trim', array_pad(explode(' ', $pairs), 2, 'ASC'));
            if (!isset($select[$sortfield]) || isset($orderby[$sortfield])) {
                continue;
            }
            $orderby[$sortfield] = $select[$sortfield] . ' ' . strtoupper($sortorder);
        }
        // We just order by name for now
        $q->setorder('name', 'ASC');

        if (!empty($numitems)) {
            $q->setrowstodo($numitems);
            if (empty($startnum)) {
                $startnum = 1;
            }
            $q->setstartat($startnum - 1);
        }
        $q->run();

        $items = [];
        foreach ($q->output() as $item) {

            // Add systemid as alternative to id CHECKME: can we settle on id?
            $item['systemid'] = $item['id'];

            if ($this->mem()->has('Mod.Infos', $item['regid'])) {
                // Merge cached info with db info
                $item += $this->mem()->get('Mod.Infos', $item['regid']);
            } else {
                $item['displayname'] = $this->mod()->getDisplayName($item['name']);
                $item['displaydescription'] = $this->mod()->getDisplayDescription($item['name']);
                // Shortcut for os prepared directory
                $item['osdirectory'] = $this->prep()->path($item['directory']);

                $this->mem()->set('Mod.BaseInfos', $item['name'], $item);

                $fileinfo = $this->mod()->getFileInfo($item['osdirectory']);
                if (!empty($fileinfo)) {
                    $item = array_merge($fileinfo, $item);
                    $this->mem()->set('Mod.Infos', $item['regid'], $item);
                    switch ($item['state']) {
                        case ixarMod::STATE_MISSING_FROM_UNINITIALISED:
                            $item['state'] = ixarMod::STATE_UNINITIALISED;
                            break;
                        case ixarMod::STATE_MISSING_FROM_INACTIVE:
                            $item['state'] = ixarMod::STATE_INACTIVE;
                            break;
                        case ixarMod::STATE_MISSING_FROM_ACTIVE:
                            $item['state'] = ixarMod::STATE_ACTIVE;
                            break;
                        case ixarMod::STATE_MISSING_FROM_UPGRADED:
                            $item['state'] = ixarMod::STATE_UPGRADED;
                            break;
                    }
                }
            }
            $items[] = $item;
        }

        return $items;
    }
}
