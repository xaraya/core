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
use xarController;
use xarSecurity;
use xarVar;
use sys;

sys::import('modules.blocks.method');

/**
 * blocks userapi getitemtypes function
 * @extends MethodClass<UserApi>
 */
class GetitemtypesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Utility function to retrieve the list of itemtypes of this module (if any).
     * @param array<string,mixed> $args array of optional parameters<br/>
     * @return array the itemtypes of this module and their description *
     * @see UserApi::getitemtypes()
     */
    public function __invoke(array $args = [])
    {
        $itemtypes = [];

        if (xarSecurity::check('EditBlocks', 0)) {
            $showurl = true;
        } else {
            $showurl = false;
        }

        $name = xarML('Block Types');
        $itemtypes[1] = ['label' => xarVar::prepForDisplay($name),
            'title' => xarVar::prepForDisplay(xarML('Display #(1)', $name)),
            'url'   => $showurl ? xarController::URL('blocks', 'admin', 'view_types') : '',
        ];

        $name = xarML('Block Groups');
        $itemtypes[2] = ['label' => xarVar::prepForDisplay($name),
            'title' => xarVar::prepForDisplay(xarML('Display #(1)', $name)),
            //'url'   => $showurl ? xarController::URL('blocks','admin','view_groups') : ''
            'url'   => '',
        ];

        $name = xarML('Block Instances');
        $itemtypes[3] = ['label' => xarVar::prepForDisplay($name),
            'title' => xarVar::prepForDisplay(xarML('Display #(1)', $name)),
            'url'   => $showurl ? xarController::URL('blocks', 'admin', 'view_instances') : '',
        ];

        return $itemtypes;
    }
}
