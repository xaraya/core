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
 * themes admin new_config function
 * @extends MethodClass<AdminGui>
 */
class NewConfigMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup * @see AdminGui::newConfig()
     */

    public function __invoke(array $args = [])
    {
        if (!xarSecurity::check('AddThemes')) {
            return;
        }

        $data = [];
        if (!xarVar::fetch('confirm', 'bool', $data['confirm'], false, xarVar::NOT_REQUIRED)) {
            return;
        }

        $data['object'] = DataObjectFactory::getObject(['name' => 'themes_configurations']);
        if ($data['confirm']) {

            // we only retrieve 'preview' from the input here - the rest is handled by checkInput()
            if (!xarVar::fetch('preview', 'str', $preview, null, xarVar::DONT_SET)) {
                return;
            }

            // Check for a valid confirmation key
            if (!xarSec::confirmAuthKey()) {
                return;
            }

            // Get the data from the form
            $isvalid = $data['object']->checkInput();

            if (!$isvalid) {
                // Bad data: redisplay the form with error messages
                $data['context'] ??= $this->getContext();
                return xarTpl::module('themes', 'admin', 'new_config', $data);
            } else {
                // Good data: create the item
                $itemid = $data['object']->createItem();

                // Jump to the next page
                xarController::redirect(xarController::URL('themes', 'admin', 'view_configs'), null, $this->getContext());
                return true;
            }
        }
        return $data;
    }
}
