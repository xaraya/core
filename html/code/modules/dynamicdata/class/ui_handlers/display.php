<?php
/**
 * Dynamic Object User Interface Handler
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 * @todo try to replace xarTplModule with xarTplObject
 */

sys::import('modules.dynamicdata.class.ui_handlers.default');
/**
 * Dynamic Object User Interface Handler
 *
 * @package Xaraya eXtensible Management System
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

        if(!isset($this->object)) 
        {
            $this->object =& DataObjectMaster::getObject($this->args);
            if(empty($this->object)) 
                return;

            if(empty($this->tplmodule)) 
            {
                $modname = xarMod::getName($this->object->moduleid);
                $this->tplmodule = $modname;
            }
        }
        $title = xarML('Display #(1)', $this->object->label);
        xarTplSetPageTitle(xarVarPrepForDisplay($title));

        if (!empty($this->args['itemid'])) {
            // get the requested item
            $itemid = $this->object->getItem();
            if(empty($itemid) || $itemid != $this->object->itemid) 
                throw new BadParameterException(
                    null,
                    'The itemid when displaying the object was found to be invalid'
                );

            // call item display hooks for this item
            $this->object->callHooks('display');

        } elseif (!empty($this->args['values'])) {
            // always set the properties based on the given values !?
            //$this->object->setFieldValues($this->args['values']);
            // check any given input values but suppress errors for now
            $this->object->checkInput($this->args['values'], 1);

        } else {
            // show a blank object
        }

        //$links = $this->object->getLinkedObjects();
        //echo var_dump($links);
        $this->object->getLinkedObjects();

        $this->object->viewfunc = $this->func;
        return xarTplObject(
            $this->tplmodule, $this->object->template, 'ui_display',
            array('object' => $this->object,
                  'hooks'  => $this->object->hookoutput)
        );
    }
}

?>
