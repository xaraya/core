<?php
/**
 * Dynamic Object User Interface Handler
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

sys::import('modules.dynamicdata.class.ui_handlers.default');
/**
 * Dynamic Object User Interface Handler
 *
 * @package modules
 * @subpackage dynamicdata
 */
class DataObjectDisplayHandler extends DataObjectDefaultHandler
{
    public $method = 'display';

    /**
     * Run the ui 'display' method
     *
     * @param $args['method'] the ui method we are handling is 'display' here
     * @param $args['itemid'] item id of the object to display, and/or
     * @param $args['preview'] true if you want dd to call checkInput() = standard dd preview using GET/POST params, or
     * @param $args['values'] array of predefined field values to use = ui-specific preview using arguments in your call
     * @return string output of xarTplObject() using 'ui_display'
     */
    function run(array $args = array())
    {
        if(!xarVarFetch('preview', 'isset', $args['preview'], NULL, XARVAR_DONT_SET)) 
            return;

        if(!xarVarFetch('values', 'isset', $args['values'], NULL, XARVAR_DONT_SET)) 
            return;

        if(!empty($args) && is_array($args) && count($args) > 0) 
            $this->args = array_merge($this->args, $args);

        if (!empty($this->args['object']) && !empty($this->args['method'])) {
            // Get a cache key for this object method if it's suitable for object caching
            $cacheKey = xarCache::getObjectKey($this->args['object'], $this->args['method'], $this->args);
            // Check if the object method is cached
            if (!empty($cacheKey) && xarObjectCache::isCached($cacheKey)) {
                // Return the cached object method output
                return xarObjectCache::getCached($cacheKey);
            }
        }

        if(!isset($this->object)) 
        {
            $this->object =& DataObjectMaster::getObject($this->args);
            if(empty($this->object) || (!empty($this->args['object']) && $this->args['object'] != $this->object->name)) 
                return xarController::$response->NotFound(xarML('Object #(1) seems to be unknown', $this->args['object']));

            if(empty($this->tplmodule)) 
            {
                $modname = xarMod::getName($this->object->moduleid);
                $this->tplmodule = $modname;
            }
        }
        $title = xarML('Display #(1)', $this->object->label);
        xarTplSetPageTitle(xarVarPrepForDisplay($title));

        if (!empty($this->args['itemid'])) {
            if (!$this->object->checkAccess('display'))
                return xarController::$response->Forbidden(xarML('Display Itemid #(1) of #(2) is forbidden', $this->args['itemid'], $this->object->label));

            // get the requested item
            $itemid = $this->object->getItem();
            if(empty($itemid) || $itemid != $this->object->itemid) 
                return xarController::$response->NotFound(xarML('Itemid #(1) of #(2) seems to be invalid', $this->args['itemid'], $this->object->label));

            // call item display hooks for this item
            $this->object->callHooks('display');

        } elseif (!empty($this->args['values'])) {
            if (!$this->object->checkAccess('display'))
                return xarResponse::Forbidden(xarML('Display #(1) is forbidden', $this->object->label));

            // always set the properties based on the given values !?
            //$this->object->setFieldValues($this->args['values']);
            // check any given input values but suppress errors for now
            $this->object->checkInput($this->args['values'], 1);

        } else {
            // show a blank object
        }

        $output = xarTplObject(
            $this->tplmodule, $this->object->template, 'ui_display',
            array('object' => $this->object,
                  'hooks'  => $this->object->hookoutput,
                  'tpltitle' => $this->tpltitle)
        );

        // Set the output of the object method in cache
        if (!empty($cacheKey)) {
            xarObjectCache::setCached($cacheKey, $output);
        }
        return $output;
    }
}

?>