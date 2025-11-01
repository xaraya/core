<?php

/**
 * @package modules\blocks
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Blocks\UserApi;

use Xaraya\Modules\Blocks\MethodClass;
use Xaraya\Modules\Blocks\UserApi;
use Xaraya\Modules\Blocks\TypesApi;
use Xaraya\Modules\Blocks\InstancesApi;
use sys;

sys::import('modules.blocks.method');

/**
 * blocks userapi getitemlinks function
 * @extends MethodClass<UserApi>
 */
class GetitemlinksMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Utility function to pass individual item links to whoever
     * @param array<string,mixed> $args array of optional parameters<br/>
     * with
     *     string   $args['itemtype'] item type (optional)<br/>
     *     array<int> $args['itemids'] array of item ids to get
     * @return array the itemlink(s) for the item(s).
     * @see UserApi::getitemlinks()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var TypesApi $typesapi */
        $typesapi = $this->typesapi();
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        /** @var InstancesApi $instancesapi */
        $instancesapi = $this->instancesapi();

        if (empty($itemtype)) {
            $itemtype = 3; // block instances
        }
        if (!empty($itemids) && is_array($itemids)) {
            $itemids = array_filter($itemids);
        }
        $itemlinks = [];

        if ($this->sec()->checkAccess('EditBlocks', 0)) {
            $showurl = true;
        } else {
            $showurl = false;
        }

        switch ($itemtype) {
            case 1: // block types
                $param = [];
                if (!empty($itemids) && count($itemids) == 1) {
                    $param['type_id'] = $itemids[0];
                }
                $types = $typesapi->getitems($param);
                if (empty($itemids)) {
                    $itemids = array_keys($types);
                }
                foreach ($itemids as $itemid) {
                    if (!isset($types[$itemid])) {
                        continue;
                    }
                    $label = $types[$itemid]['module'] . '/' . $types[$itemid]['type'];
                    $itemlinks[$itemid] = ['label' => $this->prep()->text($label),
                        'title' => $this->ml('Modify Block Type'),
                        'url'   => $showurl ? $this->ctl()->getModuleURL(
                            'blocks',
                            'admin',
                            'modify_type',
                            ['type_id' => $itemid]
                        ) : ''];
                }
                break;
                /*
                @TODO: refactor this for blockgroup blocks
                case 2: // block groups
                    $param = array();
                    if (!empty($itemids) && count($itemids) == 1) {
                        $param['id'] = $itemids[0];
                    }
                    $groups = $userapi->getallgroups($param);
                    if (empty($itemids)) {
                        $itemids = array_keys($groups);
                    }
                    foreach ($itemids as $itemid) {
                        if (!isset($groups[$itemid])) continue;
                        $label = $groups[$itemid]['name'];
                        $itemlinks[$itemid] = array('label' => $this->prep()->text($label),
                                                    'title' => $this->ml('View Block Group'),
                                                    'url'   => $showurl ? $this->ctl()->getModuleURL('blocks', 'admin', 'view_groups',
                                                                                    array('id' => $itemid)) : '');
                    }
                    break;
                */
            case 3: // block instances
            default:
                $param = [];
                if (!empty($itemids)) {
                    $param['block_id'] = $itemids;
                }
                $instances = $instancesapi->getitems($param);
                if (empty($itemids)) {
                    $itemids = array_keys($instances);
                }
                foreach ($itemids as $itemid) {
                    if (!isset($instances[$itemid])) {
                        continue;
                    }
                    $label = $instances[$itemid]['name'];
                    $itemlinks[$itemid] = ['label' => $this->prep()->text($label),
                        'title' => $this->ml('Modify Block Instance'),
                        'url'   => $showurl ? $this->ctl()->getModuleURL(
                            'blocks',
                            'admin',
                            'modify_instance',
                            ['block_id' => $itemid]
                        ) : ''];
                }
                break;
        }

        return $itemlinks;
    }
}
