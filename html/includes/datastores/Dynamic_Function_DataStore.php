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
            if (!xarModIsAvailable($fmod)) {
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
                } elseif (xarCurrentErrorType() != XAR_NO_EXCEPTION) {
                    // ignore any exceptions on retrieval for now
                    xarExceptionFree();
                }
            } else {
            // TODO: don't we want auto-loading for xarModFunc too ???
                // try to load the module GUI
                if (!xarModLoad($fmod,$ftype)) {
                    if (xarCurrentErrorType() != XAR_NO_EXCEPTION) {
                        // ignore any exceptions on retrieval for now
                        xarExceptionFree();
                    }
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
                } elseif (xarCurrentErrorType() != XAR_NO_EXCEPTION) {
                    // ignore any exceptions on retrieval for now
                    xarExceptionFree();
                }
            }
        }
    }
}

?>