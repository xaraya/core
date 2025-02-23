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
use Xaraya\Modules\Blocks\TypesApi;
use DataPropertyMaster;
use xarBlock;
use xarController;
use xarMod;
use xarModVars;
use xarSecurity;
use xarVar;
use sys;

sys::import('modules.blocks.method');

/**
 * blocks admin view_types function
 * @extends MethodClass<AdminGui>
 */
class ViewTypesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * View block types
     * @author Jim McDonald
     * @author Paul Rosania
     * @return array|void Display template data array
     * @see AdminGui::viewTypes()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var TypesApi $typesapi */
        $typesapi = $this->typesapi();
        // Security - checkme: Edit vs Manage?
        if (!xarSecurity::check('ManageBlocks')) {
            return;
        }

        // Refresh block types
        if (!$typesapi->refresh()) {
            return;
        }

        $data = [];
        $this->var()->check(
            'startnum',
            $data['startnum'],
            'int:1',
            1
        );
        $data['items_per_page'] = xarModVars::get('blocks', 'items_per_page');
        // get types from db
        $items = $typesapi->getitems([
            'startnum' => $data['startnum'],
            'numitems' => $data['items_per_page'],
        ]);
        $data['total'] = $typesapi->countitems();

        $access_property = DataPropertyMaster::getProperty(['name' => 'access']);

        foreach ($items as $type_id => $item) {
            $item['info_link'] = [
                'label' => xarML('Info'),
                'title' => xarML('View detail information about this block type'),
                'url' => xarController::URL(
                    'blocks',
                    'admin',
                    'modify_type',
                    ['type_id' => $type_id]
                ),
            ];
            $item['modify_link'] = [
                'label' => xarML('Config'),
                'title' => xarML('View or modify default configuration for this block type'),
                'url' => !xarSecurity::check('AdminBlocks', 0) ? '' :
                    xarController::URL(
                        'blocks',
                        'admin',
                        'modify_type',
                        ['type_id' => $type_id, 'interface' => 'config']
                    ),
            ];
            $item['preview_link'] = [
                'label' => xarML('Preview'),
                'title' => xarML('View a preview of this block type'),
                'url' => empty($item['type_info']['show_preview']) ? '' :
                    xarController::URL(
                        'blocks',
                        'admin',
                        'modify_type',
                        ['type_id' => $type_id, 'interface' => 'display', 'block_method' => 'preview']
                    ),
            ];
            $item['help_link'] = [
                'label' => xarML('Help'),
                'title' => xarML('View help information about this block type'),
                'url' => empty($item['type_info']['show_help']) ? '' :
                    xarController::URL(
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
                'label' => xarML('Add'),
                'title' => xarML('Create a new instance of this block type'),
                'url' => (!$access_property->check($access) || $item['type_state'] != xarBlock::TYPE_STATE_ACTIVE) ? '' :
                    xarController::URL(
                        'blocks',
                        'admin',
                        'new_instance',
                        ['type_id' => $type_id, 'phase' => 'form']
                    ),
            ];
            $items[$type_id] = $item;
        }
        $data['types'] = $items;
        $data['type_states'] = $typesapi->getstates();

        return $data;
    }
}
