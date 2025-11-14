<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\DynamicData\AdminGui;

use Xaraya\Modules\DynamicData\MethodClass;
use Xaraya\Modules\DynamicData\AdminGui;
use Xaraya\Modules\DynamicData\AdminApi;
use DataObjectDescriptor;
use FieldTypeProperty;

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
     * @see AdminGui::viewPropertydefs()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security
        if (!$this->sec()->checkAccess('AdminDynamicData')) {
            return;
        }

        $data = $adminapi->menu();

        $data['authid'] = $this->sec()->genAuthKey();

        if (!$this->mod()->apiLoad('dynamicdata', 'user')) {
            return;
        }
        $data['fields'] = $this->prop()->getPropertyTypes();
        if (!isset($data['fields']) || $data['fields'] == false) {
            $data['fields'] = [];
        }

        // FIXME: This may not work when moving property classes around manually !
        //$data['fieldtypeprop'] =& $this->prop()->getProperty(array('type' => 'fieldtype'));

        $descriptor = new DataObjectDescriptor(['type' => 'fieldtype'], $this->getParent());
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
