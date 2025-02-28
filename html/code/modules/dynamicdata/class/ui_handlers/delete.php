<?php
/**
 * Dynamic Object User Interface Handler
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

namespace Xaraya\DataObject\Handlers;

use DataObject;
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
     * @return string|bool|void output of data()->template() using 'ui_delete'
     */
    public function run(array $args = [])
    {
        $this->var()->check('cancel', $args['cancel']);
        $this->var()->check('confirm', $args['confirm']);
        $this->var()->check('return_url', $args['return_url']);
        if (!empty($args['cancel'])) {
            if (empty($args['return_url'])) {
                $args['return_url'] = $this->getReturnURL();
            }

            $this->ctl()->redirect($args['return_url']);
            // Return
            return true;
        }

        if (!empty($args) && is_array($args) && count($args) > 0) {
            $this->args = array_merge($this->args, $args);
        }

        if (!isset($this->object)) {
            // set context if available in handler
            $this->object = $this->data()->getObject($this->args);
            if (empty($this->object) || (!empty($this->args['object']) && $this->args['object'] != $this->object->name)) {
                $msg = $this->mls()->translate('Object #(1) seems to be unknown', $this->args['object']);
                return $this->ctl()->notFound($msg);
            }

            if (empty($this->tplmodule)) {
                // set in DataObjectDescriptor::getObjectID()
                $this->tplmodule = $this->object->tplmodule;
            }
        } else {
            // set context if available in handler
            $this->object->setContext($this->getContext());
        }
        assert($this->object instanceof DataObject);

        if (!$this->object->checkAccess('delete')) {
            $msg = $this->mls()->translate('Delete Itemid #(1) of #(2) is forbidden', $this->args['itemid'], $this->object->label);
            return $this->ctl()->forbidden($msg);
        }

        $itemid = $this->object->getItem();
        if (empty($itemid) || $itemid != $this->object->itemid) {
            $msg = $this->mls()->translate('Itemid #(1) of #(2) seems to be invalid', $this->args['itemid'], $this->object->label);
            return $this->ctl()->notFound($msg);
        }

        if (!empty($args['confirm'])) {
            if (!$this->sec()->confirmAuthKey()) {
                return $this->ctl()->badRequest('bad_author');
            }

            $itemid = $this->object->deleteItem();

            if (empty($itemid)) {
                return;
            } // throw back

            if (empty($args['return_url'])) {
                $args['return_url'] = $this->getReturnURL();
            }

            $this->ctl()->redirect($args['return_url']);
            // Return
            return true;
        }

        $title = $this->mls()->translate('Delete #(1)', $this->object->label);
        $this->tpl()->setPageTitle($this->var()->prep($title));

        // add data to original method args
        $data = array_replace($args, [
            'object' => $this->object,
            'context' => $this->getContext(),
            'authid' => $this->sec()->genAuthKey(),
            'tpltitle' => $this->tpltitle,
            'modtitle' => ucwords($this->object->tplmodule),
            'return_url' => $args['return_url'],
        ]);

        return $this->data()->template(
            'ui_delete',
            $data
        );
    }
}
