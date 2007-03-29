<?php
// TODO: turn this into an xml file
    function dynamicdata_dataapi_adminmenu()
    {
        return array(
                array('includes' => array('main','overview'), 'target' => 'overview', 'label' => xarML('DynamicData Overview')),
                array('mask' => 'EditDynamicData', 'includes' => array('view','new','modify','modifyprop'), 'target' => 'view', 'title' => xarML('View dataobjects using dynamic data'), 'label' => xarML('DataObjects')),
                array('mask' => 'AdminDynamicData', 'includes' => 'modifyconfig', 'target' => 'modifyconfig', 'title' => xarML('Configure the default dataproperty types'), 'label' => xarML('DataProperty Types')),
//                array('mask' => 'AdminDynamicData', 'includes' => 'modifyconfig', 'target' => 'modifyconfig', 'title' => xarML('Configure relationships'), 'label' => xarML('Relationships')),
                array('mask' => 'AdminDynamicData', 'includes' => array('utilities','query','util'), 'target' => 'utilities', 'title' => xarML('Import/export and other utilities'), 'label' => xarML('Utilities')),
        );
    }
?>