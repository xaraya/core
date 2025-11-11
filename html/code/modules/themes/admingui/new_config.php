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
        if (!$this->sec()->checkAccess('AddThemes')) {
            return;
        }

        $data = [];
        $this->var()->find('confirm', $data['confirm'], 'bool', false);

        $data['object'] = $this->data()->getObject(['name' => 'themes_configurations']);
        if ($data['confirm']) {

            // we only retrieve 'preview' from the input here - the rest is handled by checkInput()
            $this->var()->check('preview', $preview, 'str', null);

            // Check for a valid confirmation key
            if (!$this->sec()->confirmAuthKey()) {
                return;
            }

            // Get the data from the form
            $isvalid = $data['object']->checkInput();

            if (!$isvalid) {
                // Bad data: redisplay the form with error messages
                $data['context'] ??= $this->getContext();
                return $this->tpl()->module('themes', 'admin', 'new_config', $data);
            } else {
                // Good data: create the item
                $itemid = $data['object']->createItem();

                // Jump to the next page
                $this->ctl()->redirect($this->ctl()->getModuleURL('themes', 'admin', 'view_configs'));
                return true;
            }
        }
        return $data;
    }
}
