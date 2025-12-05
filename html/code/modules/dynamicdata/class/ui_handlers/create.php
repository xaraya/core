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
class CreateHandler extends DefaultHandler
{
    public string $method = 'create';

    /**
     * Run the ui 'create' method
     *
     * @param array<string, mixed> $args
     * with
     *     $args['method'] the ui method we are handling is 'create' here
     *     $args['preview'] true if you want dd to call checkInput() = standard dd preview using GET/POST params, or
     *     $args['values'] array of predefined field values to use = ui-specific preview using arguments in your call
     *     $args['confirm'] true if the user confirms
     *     $args['return_url'] the url to return to when finished (defaults to the object view / module)
     * @return string|bool|void output of $this->template() using 'ui_create'
     */
    public function run(array $args = [])
    {
        $this->var()->check('preview', $args['preview']);
        $this->var()->check('confirm', $args['confirm']);
        $this->var()->check('values', $args['values']);
        $this->var()->check('return_url', $args['return_url']);

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

        if (!$this->object->checkAccess('create')) {
            $msg = $this->mls()->translate('Create #(1) is forbidden', $this->object->label);
            return $this->ctl()->forbidden($msg);
        }

        // there's no item to get here yet
        //$this->object->getItem();

        if (!empty($this->args['values'])) {
            // always set the properties based on the given values !?
            //$this->object->setFieldValues($this->args['values']);
            // check any given input values but suppress errors for now
            $this->object->checkInput($this->args['values'], 1);
        }

        if (!empty($args['preview']) || !empty($args['confirm'])) {
            if (!empty($args['confirm']) && !$this->sec()->confirmAuthKey()) {
                return $this->ctl()->badRequest('bad_author');
            }

            $isvalid = $this->object->checkInput($args);

            if ($isvalid && !empty($args['confirm'])) {
                $itemid = $this->object->createItem();

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
            $args['preview'] = true;
        }

        $title = $this->mls()->translate('New #(1)', $this->object->label);
        $this->tpl()->setPageTitle($this->prep()->text($title));

        // call item new hooks for this item
        $this->object->callHooks('new');

        // add data to original method args
        $data = array_replace($args, [
            'object'  => $this->object,
            'context' => $this->getContext(),
            'preview' => $args['preview'],
            'authid'  => $this->sec()->genAuthKey(),
            'hooks'   => $this->object->hookoutput,
            'tpltitle' => $this->tpltitle,
            'modtitle' => ucwords($this->object->tplmodule),
            'return_url' => $args['return_url'],
        ]);

        return $this->template(
            'ui_create',
            $data
        );
    }
}
