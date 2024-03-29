<?php
/**
 * Data Store is offered by a user function
 *
 * @package core\datastores
 * @subpackage datastores
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
**/

namespace Xaraya\DataObject\DataStores;

use xarController;
use xarMod;
use DataProperty;
use sys;

sys::import('xaraya.datastores.basic');

/**
 * Handly function data store
 *
 * @deprecated 2.0.0 no longer in use
 */
class Dynamic_Function_DataStore extends BasicDataStore
{
    /** @var mixed */
    public $itemtype;

    /**
     * Get the field name used to identify this property (the property validation holds the function name here - for now...)
     * @param DataProperty $property
     * @return string|null
     */
    public function getFieldName(DataProperty &$property)
    {
        if (property_exists($property, 'validation')) {
            return $property->validation;
        }
        return null;
    }

    /**
     * Summary of setPrimary
     * @param DataProperty $property
     * @return void
     */
    public function setPrimary(DataProperty &$property)
    {
        // not applicable !?
    }

    // TODO: support different functions for the different methods,
    //       and/or pass an 'action' argument to the function, and/or...

    /**
     * Summary of getItem
     * @param array<string, mixed> $args
     * @return mixed
     */
    public function getItem(array $args = [])
    {
        $modid    = $args['moduleid'];
        $itemtype = $args['itemtype'];
        $itemid   = $args['itemid'];
        $modname  = $args['modname'];

        foreach (array_keys($this->fields) as $function) {
            // split into module, type and function
            // TODO: improve this ?
            [$fmod, $ftype, $ffunc] = explode('_', $function);
            // see if the module is available
            if (!xarMod::isAvailable($fmod)) {
                continue;
            }
            // see if we're dealing with an API function or a GUI one
            if (preg_match('/api$/', $ftype)) {
                $ftype = preg_replace('/api$/', '', $ftype);
                // try to invoke the function with some common parameters
                // TODO: standardize this, or allow the admin to specify the arguments
                $value = xarMod::apiFunc(
                    $fmod,
                    $ftype,
                    $ffunc,
                    ['modname' => $modname,
                                             'modid' => $modid,
                                             'itemtype' => $itemtype,
                                             'itemid' => $itemid,
                                             'objectid' => $itemid]
                );
                // see if we got something interesting in return
                if (isset($value)) {
                    $this->fields[$function]->value = $value;
                }
            } else {
                // TODO: don't we want auto-loading for xarMod::guiFunc too ???
                // try to load the module GUI
                if (!xarMod::load($fmod, $ftype)) {
                    continue;
                }
                // try to invoke the function with some common parameters
                // TODO: standardize this, or allow the admin to specify the arguments
                $value = xarMod::guiFunc(
                    $fmod,
                    $ftype,
                    $ffunc,
                    ['modname' => $modname,
                                          'modid' => $modid,
                                          'itemtype' => $itemtype,
                                          'itemid' => $itemid,
                                          'objectid' => $itemid]
                );
                // see if we got something interesting in return
                if (isset($value)) {
                    $this->fields[$function]->value = $value;
                }
            }
        }
        return $itemid;
    }

    /**
     * Fetch a list of the values for all items in the datastore
     * @param array<string, mixed> $args
     * @return void
     */
    public function getItems(array $args = [])
    {
        /* don't bother if there are no item ids set */
        if (empty($this->_itemids)) {
            return;
        }

        /* default values - you shouldn't rely on these! */
        if (!isset($args['modname'])) {
            $mod = xarController::getRequest()->getModule();
            $args['modname'] = $mod;
        }
        if (!isset($args['modid'])) {
            $args['modid'] = xarMod::getRegID($args['modname']);
        }
        if (!isset($args['itemtype'])) {
            $args['itemtype'] = $this->itemtype;
        }
        if (!isset($args['objectid'])) {
            $args['objectid'] = '';
        }
        $items = [];

        /* fetch the items */
        //xarLog::message(var_export($this, true));
        foreach ($this->_itemids as $itemid) {
            $args['itemid'] = $itemid;
            $this->getItem($args);

            /* save the result */
            foreach (array_keys($this->fields) as $function) {
                $this->fields[$function]->setItemValue(
                    $itemid,
                    $this->fields[$function]->value
                );
            }
        }
    }
}
