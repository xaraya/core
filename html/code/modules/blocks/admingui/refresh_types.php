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
use ixarBlock;
use xarBlock;
use sys;

sys::import('modules.blocks.method');

/**
 * blocks admin refresh_types function
 * @extends MethodClass<AdminGui>
 */
class RefreshTypesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     *
     * @author Chris Powis <crisp@xaraya.com>
     * @return array|void Returns data display array
     * @see AdminGui::refreshTypes()
     */
    public function __invoke(array $args = [])
    {
        /** @var TypesApi $typesapi */
        $typesapi = $this->typesapi();
        if (!$this->sec()->checkAccess('AdminBlocks')) {
            return;
        }

        $data = [];

        $old_db = $typesapi->getitems();

        if (!$typesapi->refresh()) {
            return;
        }

        $new_db = $typesapi->getitems();

        $unchanged = [];

        $new = [];
        $missing = [];
        $error = [];
        $unavailable = [];
        $activated = [];

        foreach ($new_db as $type_id => $type) {
            if (isset($old_db[$type_id]) && $type['type_state'] == $old_db[$type_id]['type_state']) {
                $unchanged[$type_id] = $type;
            } else {
                if (!isset($old_db[$type_id])) {
                    $new[$type_id] = $type;
                }
                switch ($type['type_state']) {
                    case ixarBlock::TYPE_STATE_ACTIVE:
                        $activated[$type_id] = $type;
                        break;
                    case ixarBlock::TYPE_STATE_ERROR:
                        $error[$type_id] = $type;
                        break;
                    case ixarBlock::TYPE_STATE_MISSING:
                        $missing[$type_id] = $type;
                        break;
                    case ixarBlock::TYPE_STATE_MOD_UNAVAILABLE:
                        $unavailable[$type_id] = $type;
                        break;
                }
            }
        }

        $data['unchanged'] = $unchanged;
        $data['new'] = $new;
        $data['missing'] = $missing;
        $data['error'] = $error;
        $data['unavailable'] = $unavailable;
        $data['activated'] = $activated;
        $data['type_states'] = $typesapi->getstates();
        return $data;
    }
}
