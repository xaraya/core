<?php
/**
 * Data Store is managed by a hook/utility module
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

use xarMod;
use DataProperty;
use DataPropertyMaster;
use sys;

sys::import('xaraya.datastores.basic');

/**
 * Class to handle hook datastore
 *
**/
class HookDataStore extends BasicDataStore
{
    /**
     * Get the field name used to identify this property (we use the hook name here)
     */
    public function getFieldName(DataProperty &$property)
    {
        // check if this is a known module, based on the name of the property type
        $proptypes = DataPropertyMaster::getPropertyTypes();
        $curtype = $property->type;
        if (!empty($proptypes[$curtype]['name'])) {
            return $proptypes[$curtype]['name'];
        }
        return null;
    }

    /**
     * Set the primary key for this data store (only 1 allowed for now)
     * @param DataProperty $property
     * @return void
     */
    public function setPrimary(DataProperty &$property)
    {
        // see OrderedDataStore
    }

    public function getItem(array $args = [])
    {
        $modid = $args['moduleid'];
        $itemtype = $args['itemtype'];
        $itemid = $args['itemid'];
        $modname = $args['modname'];

        foreach (array_keys($this->fields) as $hook) {
            if (xarMod::isAvailable($hook)) {
                // TODO: find some more consistent way to do this !
                $value = xarMod::apiFunc(
                    $hook,
                    'user',
                    'get',
                    ['modname' => $modname,
                                             'modid' => $modid,
                                             'itemtype' => $itemtype,
                                             'itemid' => $itemid,
                                             'objectid' => $itemid]
                );
                // see if we got something interesting in return
                if (isset($value)) {
                    $this->fields[$hook]->value = $value;
                }
            }
        }
        return $itemid;
    }

}
