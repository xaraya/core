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
use xarCache;
use xarObjectCache;
use xarMLS;
use xarMod;
use xarResponse;
use xarTpl;
use DataObjectFactory;
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
     * @return string|void output of xarTpl::object() using 'ui_display'
     */
    public function run(array $args = [])
    {
        if (!xarVar::fetch('preview', 'isset', $args['preview'], null, xarVar::DONT_SET)) {
            return;
        }

        if (!xarVar::fetch('values', 'isset', $args['values'], null, xarVar::DONT_SET)) {
            return;
        }

        if (!empty($args) && is_array($args) && count($args) > 0) {
            $this->args = array_merge($this->args, $args);
        }

        if (!empty($this->args['object']) && !empty($this->args['method'])) {
            // Get a cache key for this object method if it's suitable for object caching
            $cacheKey = xarCache::getObjectKey($this->args['object'], $this->args['method'], $this->args);
            // Check if the object method is cached
            if (!empty($cacheKey) && xarObjectCache::isCached($cacheKey)) {
                // Return the cached object method output
                return xarObjectCache::getCached($cacheKey);
            }
        }

        // check if we want a subset of fields here (projection)
        $this->checkFieldList();

        if (!isset($this->object)) {
            // set context if available in handler
            $this->object = DataObjectFactory::getObject($this->args, $this->getContext());
            if (empty($this->object) || (!empty($this->args['object']) && $this->args['object'] != $this->object->name)) {
                return xarResponse::NotFound(xarMLS::translate('Object #(1) seems to be unknown', $this->args['object']));
            }

            if (empty($this->tplmodule)) {
                $modname = xarMod::getName($this->object->moduleid);
                $this->tplmodule = $modname;
            }
        } else {
            // set context if available in handler
            $this->object->setContext($this->getContext());
        }

        $title = xarMLS::translate('Display #(1)', $this->object->label);
        xarTpl::setPageTitle(xarVar::prepForDisplay($title));

        if (!empty($this->args['itemid'])) {
            if (!$this->object->checkAccess('display')) {
                $this->getContext()?->setStatus(403);
                return xarResponse::Forbidden(xarMLS::translate('Display Itemid #(1) of #(2) is forbidden', $this->args['itemid'], $this->object->label));
            }

            // get the requested item
            $itemid = $this->object->getItem();
            if (empty($itemid) || $itemid != $this->object->itemid) {
                return xarResponse::NotFound(xarMLS::translate('Itemid #(1) of #(2) seems to be invalid', $this->args['itemid'], $this->object->label));
            }

            // call item display hooks for this item
            $this->object->callHooks('display');
        } elseif (!empty($this->args['values'])) {
            if (!$this->object->checkAccess('display')) {
                $this->getContext()?->setStatus(403);
                return xarResponse::Forbidden(xarMLS::translate('Display #(1) is forbidden', $this->object->label));
            }

            // always set the properties based on the given values !?
            //$this->object->setFieldValues($this->args['values']);
            // check any given input values but suppress errors for now
            $this->object->checkInput($this->args['values'], 1);
        } else {
            // show a blank object
        }

        $output = xarTpl::object(
            $this->tplmodule,
            $this->object->template,
            'ui_display',
            ['object' => $this->object,
             'context' => $this->getContext(),
             'hooks'  => $this->object->hookoutput,
             'tpltitle' => $this->tpltitle]
        );

        // Set the output of the object method in cache
        if (!empty($cacheKey)) {
            xarObjectCache::setCached($cacheKey, $output);
        }
        return $output;
    }
}
