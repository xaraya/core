<?php

/**
 * @package modules\blocks
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Blocks\AdminGui;

use Xaraya\Modules\Blocks\MethodClass;
use Xaraya\Modules\Blocks\AdminGui;
use Xaraya\Modules\Blocks\InstancesApi;
use Xaraya\Modules\Blocks\TypesApi;
use ixarBlock;

/**
 * blocks admin view_instances function
 * @extends MethodClass<AdminGui>
 */
class ViewInstancesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * View block instances
     * @author Jim McDonald
     * @author Paul Rosania
     * @return array|void Teamplate display data array
     * @see AdminGui::viewInstances()
     */
    public function __invoke(array $args = [])
    {
        /** @var InstancesApi $instancesapi */
        $instancesapi = $this->instancesapi();
        /** @var TypesApi $typesapi */
        $typesapi = $this->typesapi();
        if (!$this->sec()->checkAccess('ManageBlocks')) {
            return;
        }

        $data = [];

        $this->var()->find('tab', $data['tab'], 'pre:trim:lower:str:1:', 'list');

        /** @var \AccessProperty $access_property */
        $access_property = $this->prop()->getProperty(['name' => 'access']);

        switch ($data['tab']) {
            case 'list':
                $this->var()->find('startnum', $data['startnum'], 'int:1', 1);
                $this->var()->find('filter', $data['filter'], 'pre:trim:str:1:', null);
                $data['items_per_page'] = $this->mod()->getVar('items_per_page');

                $data['total'] = $instancesapi->countitems([
                    'filter' => $data['filter'],
                ]);
                $list = $instancesapi->getitems([
                    'filter' => $data['filter'],
                    'startnum' => $data['startnum'],
                    'numitems' => $data['items_per_page'],
                ]);

                foreach ($list as $block_id => $item) {
                    // get any groups this instance belongs to
                    if (!empty($item['content']['instance_groups'])) {
                        $item['groups'] = $instancesapi->getitems([
                            'block_id' => array_keys($item['content']['instance_groups']),
                            'type_category' => 'group',
                        ]);
                    }
                    // all managers can view info about instances
                    $item['info_link'] = [
                        'label' => $this->ml('Info'),
                        'title' => $this->ml('View detail information about this block instance'),
                        'url' => $this->ctl()->getModuleURL('blocks', 'admin', 'modify_instance', ['block_id' => $block_id]),
                    ];
                    // all managers can view info about types
                    $item['type_link'] = [
                        'label' => $this->ml('Type Info'),
                        'title' => $this->ml('View detail information about this block type'),
                        'url' => $this->ctl()->getModuleURL('blocks', 'admin', 'modify_type', ['type_id' => $item['type_id']]),
                    ];
                    // check modify access
                    $args = [
                        'module' => $item['module'],
                        'component' => 'Block',
                        'instance' => $item['type'] . ":" . $item['name'] . ":" . $item['block_id'],
                        'group' => $item['content']['modify_access']['group'],
                        'level' => $item['content']['modify_access']['level'],
                    ];
                    $modify_link = (!$access_property->check($args)) ? ''
                        : $this->ctl()->getModuleURL(
                            'blocks',
                            'admin',
                            'modify_instance',
                            ['block_id' => $block_id, 'interface' => 'config']
                        );
                    $item['modify_link'] = [
                        'label' => $this->ml('Config'),
                        'title' => $this->ml('View or modify configuration of this block instance'),
                        'url' => $modify_link,
                    ];
                    // check if this block type supports previews
                    $preview_link = (empty($item['type_info']['show_preview'])) ? ''
                        : $this->ctl()->getModuleURL(
                            'blocks',
                            'admin',
                            'modify_instance',
                            ['block_id' => $block_id, 'interface' => 'display', 'block_method' => 'preview']
                        );
                    $item['preview_link'] = [
                        'label' => $this->ml('Preview'),
                        'title' => $this->ml('Display preview of this block instance'),
                        'url' => $preview_link,
                    ];
                    // check if this block type supplies help
                    $help_link = (empty($item['type_info']['show_help'])) ? ''
                        : $this->ctl()->getModuleURL(
                            'blocks',
                            'admin',
                            'modify_instance',
                            ['block_id' => $block_id, 'interface' => 'display', 'block_method' => 'help']
                        );
                    $item['help_link'] = [
                        'label' => $this->ml('Help'),
                        'title' => $this->ml('Display help information about this block type'),
                        'url' => $help_link,
                    ];
                    // check delete access
                    $args = [
                        'module' => $item['module'],
                        'component' => 'Block',
                        'instance' => $item['type'] . ":" . $item['name'] . ":" . $item['block_id'],
                        'group' => $item['content']['delete_access']['group'],
                        'level' => $item['content']['delete_access']['level'],
                    ];
                    $delete_link = (!$access_property->check($args)) ? ''
                        : $this->ctl()->getModuleURL(
                            'blocks',
                            'admin',
                            'delete_instance',
                            ['block_id' => $block_id]
                        );
                    $item['delete_link'] = [
                        'label' => $this->ml('Delete'),
                        'title' => $this->ml('Delete this block instance'),
                        'url' => $delete_link,
                    ];

                    $list[$block_id] = $item;
                }

                $data['list'] = $list;

                break;
            case 'bygroup':
                $groups = $instancesapi->getitems([
                    'type_category' => 'group',
                ]);
                $blocks = $instancesapi->getitems([
                    'type_category' => 'block',
                ]);
                $list = [];
                foreach ($groups as $group_id => $group) {

                    // all managers can view info about instances
                    $group['info_link'] = [
                        'label' => $this->ml('Info'),
                        'title' => $this->ml('View detail information about this block instance'),
                        'url' => $this->ctl()->getModuleURL('blocks', 'admin', 'modify_instance', ['block_id' => $group_id]),
                    ];
                    // all managers can view info about types
                    $group['type_link'] = [
                        'label' => $this->ml('Type Info'),
                        'title' => $this->ml('View detail information about this block type'),
                        'url' => $this->ctl()->getModuleURL('blocks', 'admin', 'modify_type', ['type_id' => $group['type_id']]),
                    ];
                    // check modify access
                    $args = [
                        'module' => $group['module'],
                        'component' => 'Block',
                        'instance' => $group['type'] . ":" . $group['name'] . ":" . $group['block_id'],
                        'group' => $group['content']['modify_access']['group'],
                        'level' => $group['content']['modify_access']['level'],
                    ];
                    $modify_link = (!$access_property->check($args)) ? ''
                        : $this->ctl()->getModuleURL(
                            'blocks',
                            'admin',
                            'modify_instance',
                            ['block_id' => $group_id, 'interface' => 'config']
                        );
                    $group['modify_link'] = [
                        'label' => $this->ml('Config'),
                        'title' => $this->ml('View or modify configuration of this block instance'),
                        'url' => $modify_link,
                    ];
                    // check if this block type supports previews
                    $preview_link = (empty($group['type_info']['show_preview'])) ? ''
                        : $this->ctl()->getModuleURL(
                            'blocks',
                            'admin',
                            'modify_instance',
                            ['block_id' => $group_id, 'interface' => 'display', 'block_method' => 'preview']
                        );
                    $group['preview_link'] = [
                        'label' => $this->ml('Preview'),
                        'title' => $this->ml('Display preview of this block instance'),
                        'url' => $preview_link,
                    ];
                    // check if this block type supplies help
                    $help_link = (empty($group['type_info']['show_help'])) ? ''
                        : $this->ctl()->getModuleURL(
                            'blocks',
                            'admin',
                            'modify_instance',
                            ['block_id' => $group_id, 'interface' => 'display', 'block_method' => 'help']
                        );
                    $group['help_link'] = [
                        'label' => $this->ml('Help'),
                        'title' => $this->ml('Display help information about this block type'),
                        'url' => $help_link,
                    ];
                    // check delete access
                    $args = [
                        'module' => $group['module'],
                        'component' => 'Block',
                        'instance' => $group['type'] . ":" . $group['name'] . ":" . $group['block_id'],
                        'group' => $group['content']['delete_access']['group'],
                        'level' => $group['content']['delete_access']['level'],
                    ];
                    $delete_link = (!$access_property->check($args)) ? ''
                        : $this->ctl()->getModuleURL(
                            'blocks',
                            'admin',
                            'delete_instance',
                            ['block_id' => $group_id]
                        );
                    $group['delete_link'] = [
                        'label' => $this->ml('Delete'),
                        'title' => $this->ml('Delete this block instance'),
                        'url' => $delete_link,
                    ];

                    if (!empty($group['content']['group_instances'])) {
                        foreach ($group['content']['group_instances'] as $block_id) {
                            if (!isset($blocks[$block_id])) {
                                continue;
                            }
                            $block = $blocks[$block_id];
                            // all managers can view info about instances
                            $block['info_link'] = [
                                'label' => $this->ml('Info'),
                                'title' => $this->ml('View detail information about this block instance'),
                                'url' => $this->ctl()->getModuleURL('blocks', 'admin', 'modify_instance', ['block_id' => $block_id]),
                            ];
                            // all managers can view info about types
                            $block['type_link'] = [
                                'label' => $this->ml('Type Info'),
                                'title' => $this->ml('View detail information about this block type'),
                                'url' => $this->ctl()->getModuleURL('blocks', 'admin', 'modify_type', ['type_id' => $block['type_id']]),
                            ];
                            // check modify access
                            $args = [
                                'module' => $block['module'],
                                'component' => 'Block',
                                'instance' => $block['type'] . ":" . $block['name'] . ":" . $block['block_id'],
                                'group' => $block['content']['modify_access']['group'],
                                'level' => $block['content']['modify_access']['level'],
                            ];
                            $modify_link = (!$access_property->check($args)) ? ''
                                : $this->ctl()->getModuleURL(
                                    'blocks',
                                    'admin',
                                    'modify_instance',
                                    ['block_id' => $block_id, 'interface' => 'config']
                                );
                            $block['modify_link'] = [
                                'label' => $this->ml('Config'),
                                'title' => $this->ml('View or modify configuration of this block instance'),
                                'url' => $modify_link,
                            ];
                            // check if this block type supports previews
                            $preview_link = (empty($block['type_info']['show_preview'])) ? ''
                                : $this->ctl()->getModuleURL(
                                    'blocks',
                                    'admin',
                                    'modify_instance',
                                    ['block_id' => $block_id, 'interface' => 'display', 'block_method' => 'preview']
                                );
                            $block['preview_link'] = [
                                'label' => $this->ml('Preview'),
                                'title' => $this->ml('Display preview of this block instance'),
                                'url' => $preview_link,
                            ];
                            // check if this block type supplies help
                            $help_link = (empty($block['type_info']['show_help'])) ? ''
                                : $this->ctl()->getModuleURL(
                                    'blocks',
                                    'admin',
                                    'modify_instance',
                                    ['block_id' => $block_id, 'interface' => 'display', 'block_method' => 'help']
                                );
                            $block['help_link'] = [
                                'label' => $this->ml('Help'),
                                'title' => $this->ml('Display help information about this block type'),
                                'url' => $help_link,
                            ];
                            // check delete access
                            $args = [
                                'module' => $block['module'],
                                'component' => 'Block',
                                'instance' => $block['type'] . ":" . $block['name'] . ":" . $block['block_id'],
                                'group' => $block['content']['delete_access']['group'],
                                'level' => $block['content']['delete_access']['level'],
                            ];
                            $delete_link = (!$access_property->check($args)) ? ''
                                : $this->ctl()->getModuleURL(
                                    'blocks',
                                    'admin',
                                    'delete_instance',
                                    ['block_id' => $block_id]
                                );
                            $block['delete_link'] = [
                                'label' => $this->ml('Delete'),
                                'title' => $this->ml('Delete this block instance'),
                                'url' => $delete_link,
                            ];
                            $group['instances'][$block_id] = $block;
                        }
                    }
                    $list[$group_id] = $group;
                }
                $data['list'] = $list;

                break;
            case 'bytype':
                $types = $typesapi->getitems();
                foreach ($types as $type_id => $item) {
                    $item['info_link'] = [
                        'label' => $this->ml('Info'),
                        'title' => $this->ml('View detail information about this block type'),
                        'url' => $this->ctl()->getModuleURL(
                            'blocks',
                            'admin',
                            'modify_type',
                            ['type_id' => $type_id]
                        ),
                    ];
                    $item['modify_link'] = [
                        'label' => $this->ml('Config'),
                        'title' => $this->ml('View or modify default configuration for this block type'),
                        'url' => !$this->sec()->checkAccess('AdminBlocks', 0) ? ''
                            : $this->ctl()->getModuleURL(
                                'blocks',
                                'admin',
                                'modify_type',
                                ['type_id' => $type_id, 'interface' => 'config']
                            ),
                    ];
                    $item['preview_link'] = [
                        'label' => $this->ml('Preview'),
                        'title' => $this->ml('View a preview of this block type'),
                        'url' => empty($item['type_info']['show_preview']) ? ''
                            : $this->ctl()->getModuleURL(
                                'blocks',
                                'admin',
                                'modify_type',
                                ['type_id' => $type_id,  'interface' => 'display', 'block_method' => 'preview']
                            ),
                    ];
                    $item['help_link'] = [
                        'label' => $this->ml('Help'),
                        'title' => $this->ml('View help information about this block type'),
                        'url' => empty($item['type_info']['show_help']) ? ''
                            : $this->ctl()->getModuleURL(
                                'blocks',
                                'admin',
                                'modify_type',
                                ['type_id' => $type_id, 'interface' => 'display', 'block_method' => 'help']
                            ),
                    ];
                    // check new instance access
                    $access = [
                        'module' => $item['module'],
                        'component' => 'Block',
                        'instance' => $item['type'] . ":All:All",
                        'group' => $item['type_info']['add_access']['group'],
                        'level' => $item['type_info']['add_access']['level'],
                    ];
                    $item['add_link'] = [
                        'label' => $this->ml('Add'),
                        'title' => $this->ml('Create a new instance of this block type'),
                        'url' => (!$access_property->check($access) || $item['type_state'] != ixarBlock::TYPE_STATE_ACTIVE) ? ''
                            : $this->ctl()->getModuleURL(
                                'blocks',
                                'admin',
                                'new_instance',
                                ['type_id' => $type_id, 'phase' => 'form']
                            ),
                    ];
                    $types[$type_id] = $item;
                }
                $instances = $instancesapi->getitems();
                foreach ($instances as $block_id => $item) {
                    // get any groups this instance belongs to
                    if (!empty($item['content']['instance_groups'])) {
                        foreach (array_keys($item['content']['instance_groups']) as $group_id) {
                            if (!isset($instances[$group_id])) {
                                continue;
                            }
                            $item['groups'][$group_id] = $instances[$group_id];
                        }
                    }
                    // all managers can view info about instances
                    $item['info_link'] = [
                        'label' => $this->ml('Info'),
                        'title' => $this->ml('View detail information about this block instance'),
                        'url' => $this->ctl()->getModuleURL('blocks', 'admin', 'modify_instance', ['block_id' => $block_id]),
                    ];
                    // all managers can view info about types
                    $item['type_link'] = [
                        'label' => $this->ml('Type Info'),
                        'title' => $this->ml('View detail information about this block type'),
                        'url' => $this->ctl()->getModuleURL('blocks', 'admin', 'modify_type', ['type_id' => $item['type_id']]),
                    ];
                    // check modify access
                    $args = [
                        'module' => $item['module'],
                        'component' => 'Block',
                        'instance' => $item['type'] . ":" . $item['name'] . ":" . $item['block_id'],
                        'group' => $item['content']['modify_access']['group'],
                        'level' => $item['content']['modify_access']['level'],
                    ];
                    $modify_link = (!$access_property->check($args)) ? ''
                        : $this->ctl()->getModuleURL(
                            'blocks',
                            'admin',
                            'modify_instance',
                            ['block_id' => $block_id, 'interface' => 'config']
                        );
                    $item['modify_link'] = [
                        'label' => $this->ml('Config'),
                        'title' => $this->ml('View or modify configuration of this block instance'),
                        'url' => $modify_link,
                    ];
                    // check if this block type supports previews
                    $preview_link = (empty($item['type_info']['show_preview'])) ? ''
                        : $this->ctl()->getModuleURL(
                            'blocks',
                            'admin',
                            'modify_instance',
                            ['block_id' => $block_id, 'interface' => 'display', 'block_method' => 'preview']
                        );
                    $item['preview_link'] = [
                        'label' => $this->ml('Preview'),
                        'title' => $this->ml('Display preview of this block instance'),
                        'url' => $preview_link,
                    ];
                    // check if this block type supplies help
                    $help_link = (empty($item['type_info']['show_help'])) ? ''
                        : $this->ctl()->getModuleURL(
                            'blocks',
                            'admin',
                            'modify_instance',
                            ['block_id' => $block_id, 'interface' => 'display', 'block_method' => 'help']
                        );
                    $item['help_link'] = [
                        'label' => $this->ml('Help'),
                        'title' => $this->ml('Display help information about this block type'),
                        'url' => $help_link,
                    ];
                    // check delete access
                    $args = [
                        'module' => $item['module'],
                        'component' => 'Block',
                        'instance' => $item['type'] . ":" . $item['name'] . ":" . $item['block_id'],
                        'group' => $item['content']['delete_access']['group'],
                        'level' => $item['content']['delete_access']['level'],
                    ];
                    $delete_link = (!$access_property->check($args)) ? ''
                        : $this->ctl()->getModuleURL(
                            'blocks',
                            'admin',
                            'delete_instance',
                            ['block_id' => $block_id]
                        );
                    $item['delete_link'] = [
                        'label' => $this->ml('Delete'),
                        'title' => $this->ml('Delete this block instance'),
                        'url' => $delete_link,
                    ];
                    $types[$item['type_id']]['instances'][$block_id] = $item;
                }

                $data['list'] = $types;

                break;
            case 'compact':
                //ugh!
                break;
        }
        $data['type_states'] = $typesapi->getstates();
        $data['instance_states'] = $instancesapi->getstates();
        $data['blocktabs'] = [
            'list' => [
                'url' => $this->ctl()->getCurrentURL(['tab' => 'list']),
                'label' => $this->ml('List'),
                'title' => $this->ml('View list of block instances'),
            ],
            'bygroup' => [
                'url' => $this->ctl()->getCurrentURL(['tab' => 'bygroup']),
                'label' => $this->ml('By Group'),
                'title' => $this->ml('View list of block instances grouped by block group'),
            ],
            'bytype' => [
                'url' => $this->ctl()->getCurrentURL(['tab' => 'bytype']),
                'label' => $this->ml('By Type'),
                'title' => $this->ml('View list of block instances grouped by block type'),
            ],
            /* drop this, we can revisit if anyone complains
            'compact' => array(
                'url' => $this->ctl()->getCurrentURL(array('tab' => 'compact')),
                'label' => $this->ml('Compact'),
                'title' => $this->ml('View a compact list of block instances'),
            ),
            */
        ];


        return $data;

    }
}
