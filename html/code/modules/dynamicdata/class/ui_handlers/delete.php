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
     * @return string|bool|void output of $this->render() using 'ui_delete'
     */
    public function run(array $args = [])
    {
        $xar = $this->getStaticServices();
        $xar->var()->check('cancel', $args['cancel']);
        $xar->var()->check('confirm', $args['confirm']);
        $xar->var()->check('return_url', $args['return_url']);
        if (!empty($args['cancel'])) {
            if (empty($args['return_url'])) {
                $args['return_url'] = $this->getReturnURL();
            }

            $xar->ctl()->redirect($args['return_url']);
            // Return
            return true;
        }

        if (!empty($args) && is_array($args) && count($args) > 0) {
            $this->args = array_merge($this->args, $args);
        }

        if (!isset($this->object)) {
            // set context if available in handler
            $this->object = $xar->data()->getObject($this->args);
            if (empty($this->object) || (!empty($this->args['object']) && $this->args['object'] != $this->object->name)) {
                $msg = $xar->mls()->translate('Object #(1) seems to be unknown', $this->args['object']);
                return $xar->ctl()->notFound($msg);
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
            $msg = $xar->mls()->translate('Delete Itemid #(1) of #(2) is forbidden', $this->args['itemid'], $this->object->label);
            return $xar->ctl()->forbidden($msg);
        }

        $itemid = $this->object->getItem();
        if (empty($itemid) || $itemid != $this->object->itemid) {
            $msg = $xar->mls()->translate('Itemid #(1) of #(2) seems to be invalid', $this->args['itemid'], $this->object->label);
            return $xar->ctl()->notFound($msg);
        }
        $modName = $this->getModName();

        if (!empty($args['confirm'])) {
            if (!$xar->sec()->confirmAuthKey($modName)) {
                return $xar->ctl()->badRequest('bad_author');
            }

            $itemid = $this->object->deleteItem();

            if (empty($itemid)) {
                return;
            } // throw back

            if (empty($args['return_url'])) {
                $args['return_url'] = $this->getReturnURL();
            }

            $xar->ctl()->redirect($args['return_url']);
            // Return
            return true;
        }

        $title = $xar->mls()->translate('Delete #(1)', $this->object->label);
        $xar->tpl()->setPageTitle($xar->prep()->text($title));

        // add data to original method args
        $data = array_replace($args, [
            'object' => $this->object,
            'authid' => $xar->sec()->genAuthKey($modName),
            'tpltitle' => $this->tpltitle,
            'modtitle' => ucwords($this->object->tplmodule),
            'return_url' => $args['return_url'],
        ]);

        return $this->render(
            'ui_delete',
            $data
        );
    }
}
