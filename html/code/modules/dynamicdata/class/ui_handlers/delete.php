<?php
/**
 * Dynamic Object User Interface Handler
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

namespace Xaraya\DataObject\Handlers;

use xarVar;
use xarMLS;
use xarMod;
use xarController;
use xarResponse;
use xarSec;
use xarTpl;
use DataObjectFactory;
use sys;

sys::import('modules.dynamicdata.class.ui_handlers.default');

/**
 * Dynamic Object User Interface Handler
 *
 */
class DeleteHandler extends DefaultHandler
{
    public string $method = 'delete';

    /**
     * Run the ui 'delete' method
     *
     * @param array<string, mixed> $args
     * with
     *     $args['method'] the ui method we are handling is 'delete' here
     *     $args['itemid'] item id of the object to delete (required here)
     *     $args['cancel'] true if the user cancels
     *     $args['confirm'] true if the user confirms
     *     $args['return_url'] the url to return to when finished (defaults to the object view / module)
     * @return string|bool|void output of xarTpl::object() using 'ui_delete'
     */
    public function run(array $args = [])
    {
        if (!xarVar::fetch('cancel', 'isset', $args['cancel'], null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('confirm', 'isset', $args['confirm'], null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('return_url', 'isset', $args['return_url'], null, xarVar::DONT_SET)) {
            return;
        }

        if (!empty($args) && is_array($args) && count($args) > 0) {
            $this->args = array_merge($this->args, $args);
        }

        if (!isset($this->object)) {
            $this->object = DataObjectFactory::getObject($this->args);
            if (empty($this->object) || (!empty($this->args['object']) && $this->args['object'] != $this->object->name)) {
                return xarResponse::NotFound(xarMLS::translate('Object #(1) seems to be unknown', $this->args['object']));
            }

            if (empty($this->tplmodule)) {
                $modname = xarMod::getName($this->object->moduleid);
                $this->tplmodule = $modname;
            }
        }
        if (!empty($args['cancel'])) {
            if (empty($args['return_url'])) {
                $args['return_url'] = $this->getReturnURL();
            }

            xarController::redirect($args['return_url'], null, $this->getContext());
            // Return
            return true;
        }
        // set context if available in handler
        $this->object->setContext($this->getContext());
        if (!$this->object->checkAccess('delete')) {
            $this->getContext()?->setStatus(403);
            return xarResponse::Forbidden(xarMLS::translate('Delete Itemid #(1) of #(2) is forbidden', $this->args['itemid'], $this->object->label));
        }

        $itemid = $this->object->getItem();
        if (empty($itemid) || $itemid != $this->object->itemid) {
            return xarResponse::NotFound(xarMLS::translate('Itemid #(1) of #(2) seems to be invalid', $this->args['itemid'], $this->object->label));
        }

        if (!empty($args['confirm'])) {
            if (!xarSec::confirmAuthKey()) {
                return xarTpl::module('privileges', 'user', 'errors', ['layout' => 'bad_author']);
            }

            $itemid = $this->object->deleteItem();

            if (empty($itemid)) {
                return;
            } // throw back

            if (empty($args['return_url'])) {
                $args['return_url'] = $this->getReturnURL();
            }

            xarController::redirect($args['return_url'], null, $this->getContext());
            // Return
            return true;
        }

        $title = xarMLS::translate('Delete #(1)', $this->object->label);
        xarTpl::setPageTitle(xarVar::prepForDisplay($title));

        return xarTpl::object(
            $this->tplmodule,
            $this->object->template,
            'ui_delete',
            ['object' => $this->object,
             'authid' => xarSec::genAuthKey(),
             'tpltitle' => $this->tpltitle,
             'return_url' => $args['return_url']]
        );
    }
}
