<?php
/**
 * Dynamic Object User Interface Handler
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.0
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
use xarSec;
use xarTpl;
use DataObjectFactory;
use DataObject;
use sys;

sys::import('modules.dynamicdata.class.ui_handlers.default');

/**
 * Dynamic Object User Interface Handler
 *
 */
class UpdateHandler extends DefaultHandler
{
    public string $method = 'update';

    /**
     * Run the ui 'update' method
     *
     * @param array<string, mixed> $args
     * with
     *     $args['method'] the ui method we are handling is 'update' here
     *     $args['itemid'] item id of the object to update (required here), and
     *     $args['preview'] true if you want dd to call checkInput() = standard dd preview using GET/POST params, or
     *     $args['values'] array of predefined field values to use = ui-specific preview using arguments in your call
     *     $args['confirm'] true if the user confirms
     *     $args['return_url'] the url to return to when finished (defaults to the object view / module)
     * @return string|bool|void output of xarTpl::object() using 'ui_update'
     */
    public function run(array $args = [])
    {
        if (!$this->xVar()->get('preview', $args['preview'])) {
            return;
        }
        if (!$this->xVar()->get('confirm', $args['confirm'])) {
            return;
        }
        if (!$this->xVar()->get('values', $args['values'])) {
            return;
        }
        if (!$this->xVar()->get('return_url', $args['return_url'])) {
            return;
        }

        if (!empty($args) && is_array($args) && count($args) > 0) {
            $this->args = array_merge($this->args, $args);
        }

        // check if we want a subset of fields here (projection)
        $this->checkFieldList();
        // @todo support updating field subsets for mongodb etc. someday
        if (!empty($this->args['fieldsubset'])) {
            $this->args['fieldsubset'] = [];
        }

        if (!isset($this->object)) {
            // set context if available in handler
            $this->object = $this->xData()->getObject($this->args);
            if (empty($this->object) || (!empty($this->args['object']) && $this->args['object'] != $this->object->name)) {
                $msg = $this->xMls()->translate('Object #(1) seems to be unknown', $this->args['object']);
                return $this->xCtl()->notFound($msg);
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

        if (!$this->object->checkAccess('update')) {
            $msg = $this->xMls()->translate('Update Itemid #(1) of #(2) is forbidden', $this->args['itemid'], $this->object->label);
            return $this->xCtl()->forbidden($msg);
        }

        $itemid = $this->object->getItem();
        if (empty($itemid) || $itemid != $this->object->itemid) {
            $msg = $this->xMls()->translate('Itemid #(1) of #(2) seems to be invalid', $this->args['itemid'], $this->object->label);
            return $this->xCtl()->notFound($msg);
        }

        if (!empty($this->args['values'])) {
            // always set the properties based on the given values !?
            //$this->object->setFieldValues($this->args['values']);
            // check any given input values but suppress errors for now
            $this->object->checkInput($this->args['values'], 1);
        }

        if (!empty($args['preview']) || !empty($args['confirm'])) {
            if (!empty($args['confirm']) && !$this->xSec()->confirmAuthKey()) {
                return $this->xCtl()->badRequest('bad_author');
            }

            $isvalid = $this->object->checkInput($args);

            if ($isvalid && !empty($args['confirm'])) {
                $itemid = $this->object->updateItem();

                if (empty($itemid)) {
                    return;
                } // throw back

                if (empty($args['return_url'])) {
                    $args['return_url'] = $this->getReturnURL();
                }

                $this->xCtl()->redirect($args['return_url']);
                // Return
                return true;
            }
            $args['preview'] = true;
        }

        $title = $this->xMls()->translate('Modify #(1)', $this->object->label);
        $this->xTpl()->setPageTitle($this->xVar()->prep($title));

        // call item modify hooks for this item
        $this->object->callHooks('modify');

        // add data to original method args
        $data = array_replace($args, [
            'object'  => $this->object,
            'context' => $this->getContext(),
            'preview' => $args['preview'],
            'authid'  => $this->xSec()->genAuthKey(),
            'hooks'   => $this->object->hookoutput,
            'tpltitle' => $this->tpltitle,
            'return_url' => $args['return_url'],
        ]);

        return $this->xTpl()->object(
            'ui_update',
            $data
        );
    }
}
