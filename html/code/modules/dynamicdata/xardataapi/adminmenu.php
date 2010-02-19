<?php
/**
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
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
                array('mask' => 'AdminDynamicData', 'includes' => 'modifyconfig', 'target' => 'modifyconfig', 'title' => xarML('Configure the default dataproperty types'), 'label' => xarML('DataProperty Types')),
//                array('mask' => 'AdminDynamicData', 'includes' => 'relations', 'target' => 'relations', 'title' => xarML('Configure relationships'), 'label' => xarML('Relationships')),
                array('mask' => 'AdminDynamicData', 'includes' => array('utilities','query','util'), 'target' => 'utilities', 'title' => xarML('Import/export and other utilities'), 'label' => xarML('Utilities')),
                array('mask' => 'AdminDynamicData', 'includes' => array('modifymoduleconfig'), 'target' => 'modifymoduleconfig', 'title' => xarML('Modify the configuration of this module'), 'label' => xarML('Modify Configuration')),
        );
    }
?>