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

use xarCache;
use xarObjectCache;
use DataObject;
use sys;

sys::import('modules.dynamicdata.class.ui_handlers.default');

/**
 * Dynamic Object User Interface Handler
 *
 */
class DisplayHandler extends DefaultHandler
{
    public string $method = 'display';

    /**
     * Run the ui 'display' method
     *
     * @param array<string, mixed> $args
     * with
     *     $args['method'] the ui method we are handling is 'display' here
     *     $args['itemid'] item id of the object to display, and/or
     *     $args['preview'] true if you want dd to call checkInput() = standard dd preview using GET/POST params, or
     *     $args['values'] array of predefined field values to use = ui-specific preview using arguments in your call
     * @return string|void output of tpl()->object() using 'ui_display'
     */
    public function run(array $args = [])
    {
        if (!$this->var()->check('preview', $args['preview'])) {
            return;
        }

        if (!$this->var()->check('values', $args['values'])) {
            return;
        }

        if (!empty($args) && is_array($args) && count($args) > 0) {
            $this->args = array_merge($this->args, $args);
        }

        $cacheKey = null;
        if (!empty($this->args['object']) && !empty($this->args['method'])) {
            // Get a cache key for this object method if it's suitable for object caching
            $cacheKey = $this->cache()->getObjectKey($this->args['object'], $this->args['method'], $this->args);
            // Check if the object method is cached
            if ($this->cache()->hasObject($cacheKey)) {
                // Return the cached object method output
                return $this->cache()->getObject($cacheKey);
            }
        }

        // check if we want a subset of fields here (projection)
        $this->checkFieldList();

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

        $title = $this->mls()->translate('Display #(1)', $this->object->label);
        $this->tpl()->setPageTitle($this->var()->prep($title));

        if (!empty($this->args['itemid'])) {
            if (!$this->object->checkAccess('display')) {
                $msg = $this->mls()->translate('Display Itemid #(1) of #(2) is forbidden', $this->args['itemid'], $this->object->label);
                return $this->ctl()->forbidden($msg);
            }

            // get the requested item
            $itemid = $this->object->getItem();
            if (empty($itemid) || $itemid != $this->object->itemid) {
                $msg = $this->mls()->translate('Itemid #(1) of #(2) seems to be invalid', $this->args['itemid'], $this->object->label);
                return $this->ctl()->notFound($msg);
            }

            // call item display hooks for this item
            $this->object->callHooks('display');
        } elseif (!empty($this->args['values'])) {
            if (!$this->object->checkAccess('display')) {
                $msg = $this->mls()->translate('Display #(1) is forbidden', $this->object->label);
                return $this->ctl()->forbidden($msg);
            }

            // always set the properties based on the given values !?
            //$this->object->setFieldValues($this->args['values']);
            // check any given input values but suppress errors for now
            $this->object->checkInput($this->args['values'], 1);
        } else {
            // show a blank object
        }

        // add data to original method args
        $data = array_replace($args, [
            'object' => $this->object,
            'context' => $this->getContext(),
            'hooks'  => $this->object->hookoutput,
            'tpltitle' => $this->tpltitle,
        ]);

        $output = $this->tpl()->object(
            'ui_display',
            $data
        );

        // Set the output of the object method in cache
        $this->cache()->setObject($cacheKey, $output);
        return $output;
    }
}
