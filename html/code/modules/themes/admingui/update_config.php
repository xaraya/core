<?php

/**
 * @package modules\themes
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Themes\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Themes\AdminGui;
use DataObjectFactory;
use xarController;
use xarSec;
use xarSecurity;
use xarTpl;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * themes admin update_config function
 * @extends MethodClass<AdminGui>
 */
class UpdateConfigMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup * @see AdminGui::updateConfig()
     */

    public function __invoke(array $args = [])
    {
        if (!xarSecurity::check('EditThemes')) {
            return;
        }

        $data = [];
        if (!xarVar::fetch('itemid', 'int', $data['itemid'], 0, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('confirm', 'bool', $data['confirm'], false, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('update', 'str', $data['update'], false, xarVar::NOT_REQUIRED)) {
            return;
        }

        $data['object'] = DataObjectFactory::getObject(['name' => 'themes_configurations']);
        $data['object']->getItem(['itemid' => $data['itemid']]);

        if ($data['confirm']) {

            // Check for a valid confirmation key
            if (!xarSec::confirmAuthKey()) {
                return;
            }

            // Get the data from the form
            $isvalid = $data['object']->checkInput();

            if (!$isvalid) {
                // Bad data: redisplay the form with error messages
                $data['context'] ??= $this->getContext();
                return xarTpl::module('themes', 'admin', 'update_config', $data);
            } else {

                // Good data: create the item
                $itemid = $data['object']->updateItem(['itemid' => $data['itemid']]);
                if ($data['update']) {
                    xarController::redirect(xarController::URL('themes', 'admin', 'view_configs'), null, $this->getContext());
                    return true;
                } else {
                    xarController::redirect(xarController::URL('themes', 'admin', 'update_config', $data), null, $this->getContext());
                    return true;
                }
            }
        }
        return $data;
    }
}
