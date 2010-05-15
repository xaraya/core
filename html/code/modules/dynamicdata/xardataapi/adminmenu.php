<?php
/**
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 */
// TODO: turn this into an xml file
    function dynamicdata_dataapi_adminmenu()
    {
        return array(
                array('includes' => array('main','overview'), 'target' => 'main', 'label' => xarML('Overview')),
                array('mask' => 'EditDynamicData', 'includes' => array('view','new','modify','modifyprop'), 'target' => 'view', 'title' => xarML('View dataobjects using dynamic data'), 'label' => xarML('DataObjects')),
                array('mask' => 'AdminDynamicData', 'includes' => 'view_propertydefs', 'target' => 'view_propertydefs', 'title' => xarML('Configure the default dataproperty types'), 'label' => xarML('DataProperty Types')),
//                array('mask' => 'AdminDynamicData', 'includes' => 'relations', 'target' => 'relations', 'title' => xarML('Configure relationships'), 'label' => xarML('Relationships')),
                array('mask' => 'AdminDynamicData', 'includes' => array('utilities','query','util'), 'target' => 'utilities', 'title' => xarML('Import/export and other utilities'), 'label' => xarML('Utilities')),
                array('mask' => 'AdminDynamicData', 'includes' => array('modifyconfig'), 'target' => 'modifyconfig', 'title' => xarML('Modify the configuration of this module'), 'label' => xarML('Modify Configuration')),
        );
    }
?>