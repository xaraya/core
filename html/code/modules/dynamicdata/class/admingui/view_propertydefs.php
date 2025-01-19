<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\DataObject\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\DataObject\AdminGui;
use DataObjectDescriptor;
use DataPropertyMaster;
use FieldTypeProperty;
use xarMod;
use xarSec;
use xarSecurity;
use sys;

sys::import('xaraya.modules.method');

/**
 * dynamicdata admin view_propertydefs function
 * @extends MethodClass<AdminGui>
 */
class ViewPropertydefsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * This is a standard function to modify the configuration parameters of the
     * module
     * @return array|void data for the template display
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!xarSecurity::check('AdminDynamicData')) {
            return;
        }

        $data = xarMod::apiFunc('dynamicdata', 'admin', 'menu');

        $data['authid'] = $this->sec()->genAuthKey();

        if (!xarMod::apiLoad('dynamicdata', 'user')) {
            return;
        }
        $data['fields'] = DataPropertyMaster::getPropertyTypes();
        if (!isset($data['fields']) || $data['fields'] == false) {
            $data['fields'] = [];
        }

        // FIXME: This may not work when moving property classes around manually !
        //$data['fieldtypeprop'] =& DataPropertyMaster::getProperty(array('type' => 'fieldtype'));
        sys::import('modules.dynamicdata.xarproperties.fieldtype');

        $descriptor = new DataObjectDescriptor(['type' => 'fieldtype']);
        $data['fieldtypeprop'] = new FieldTypeProperty($descriptor);

        $data['labels'] = [
            'id' => $this->ml('ID'),
            'name' => $this->ml('Name'),
            'label' => $this->ml('Description'),
            'informat' => $this->ml('Input Format'),
            'outformat' => $this->ml('Display Format'),
            'configuration' => $this->ml('Configuration'),
            // etc.
            'new' => $this->ml('New'),
        ];

        return $data;
    }
}
