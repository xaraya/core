<?php
/**
 * Data Store is offered by a user function
 *
 * @package dynamicdata
 * @subpackage datastores
 */

/**
 * Handly function data store
 *
 * @package dynamicdata
 *
 */
class Dynamic_Function_DataStore extends Dynamic_DataStore
{
    /**
     * Get the field name used to identify this property (the property validation holds the function name here - for now...)
     */
    function getFieldName(&$property)
    {
        return $property->validation;
    }

    function setPrimary(&$property)
    {
        // not applicable !?
    }

// TODO: support different functions for the different methods,
//       and/or pass an 'action' argument to the function, and/or...

    function getItem($args)
    {
        $modid = $args['modid'];
        $itemtype = $args['itemtype'];
        $itemid = $args['itemid'];
        $modname = $args['modname'];

        foreach (array_keys($this->fields) as $function) {
            // split into module, type and function
    // TODO: improve this ?
            list($fmod,$ftype,$ffunc) = explode('_',$function);
            // see if the module is available
            if (!xarMod::isAvailable($fmod)) {
                continue;
            }
            // see if we're dealing with an API function or a GUI one
            if (preg_match('/api$/',$ftype)) {
                $ftype = preg_replace('/api$/','',$ftype);
                // try to invoke the function with some common parameters
            // TODO: standardize this, or allow the admin to specify the arguments
                $value = xarModAPIFunc($fmod,$ftype,$ffunc,
                                       array('modname' => $modname,
                                             'modid' => $modid,
                                             'itemtype' => $itemtype,
                                             'itemid' => $itemid,
                                             'objectid' => $itemid));
                // see if we got something interesting in return
                if (isset($value)) {
                    $this->fields[$function]->setValue($value);
                } 
            } else {
            // TODO: don't we want auto-loading for xarModFunc too ???
                // try to load the module GUI
                if (!xarModLoad($fmod,$ftype)) {
                    continue;
                }
                // try to invoke the function with some common parameters
            // TODO: standardize this, or allow the admin to specify the arguments
                $value = xarModFunc($fmod,$ftype,$ffunc,
                                    array('modname' => $modname,
                                          'modid' => $modid,
                                          'itemtype' => $itemtype,
                                          'itemid' => $itemid,
                                          'objectid' => $itemid));
                // see if we got something interesting in return
                if (isset($value)) {
                    $this->fields[$function]->setValue($value);
                } 
            }
        }
        return $itemid;
    }

    /* fetch a list of the values for all items in the datastore */
    function getItems($args)
    {   
        /* don't bother if there are no item ids set */
        if (empty($this->_itemids)) {
            return array();
        }

        /* default values - you shouldn't rely on these! */
        if (!isset($args['modname'])) {
            list($mod, $type, $func) = xarRequest::getInfo();
            $args['modname'] = $mod;
        }
        if (!isset($args['modid'])) {
            $args['modid'] = xarMod::getRegID($mod);
        }
        if (!isset($args['itemtype'])) {
            $args['itemtype'] = $this->itemtype;
        }
        if (!isset($args['objectid'])) {
            $args['objectid'] = '';
        }
        $items = array();

        /* fetch the items */
        //xarLogMessage(var_export($this, true));
        foreach ($this->_itemids as $itemid) {
            $args['itemid'] = $itemid;
            $this->getItem($args);

            /* save the result */
            foreach (array_keys($this->fields) as $function) {
                $this->fields[$function]->setItemValue($itemid,
                        $this->fields[$function]->getValue());
            }
        }
    } /* getItems */
}

?>
