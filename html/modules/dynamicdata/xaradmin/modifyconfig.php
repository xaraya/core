<?php

/**
 * This is a standard function to modify the configuration parameters of the
 * module
 */
function dynamicdata_admin_modifyconfig()
{
    // Initialise the $data variable that will hold the data to be used in
    // the blocklayout template, and get the common menu configuration - it
    // helps if all of the module pages have a standard menu at the top to
    // support easy navigation
    $data = xarModAPIFunc('dynamicdata','admin','menu');

    // Security check - important to do this as early as possible to avoid
    // potential security holes or just too much wasted processing
// Security Check
	if(!xarSecurityCheck('AdminDynamicData')) return;

    // Generate a one-time authorisation code for this operation
    $data['authid'] = xarSecGenAuthKey();

    if (!xarModAPILoad('dynamicdata', 'user')) return;

    // Get the defined property types from somewhere...
    $data['fields'] = xarModAPIFunc('dynamicdata','user','getproptypes');
    if (!isset($data['fields']) || $data['fields'] == false) {
        $data['fields'] = array();
    }

    $data['fieldtypeprop'] =& Dynamic_Property_Master::getProperty(array('type' => 'fieldtype'));

    $data['labels'] = array(
                            'id' => xarML('ID'),
                            'name' => xarML('Name'),
                            'label' => xarML('Description'),
                            'informat' => xarML('Input Format'),
                            'outformat' => xarML('Display Format'),
                            'validation' => xarML('Validation'),
                        // etc.
                            'new' => xarML('New'),
                      );

    // Specify some labels and values for display
    $data['updatebutton'] = xarVarPrepForDisplay(xarML('Update Property Types'));

    // Return the template variables defined in this function
    return $data;
}

?>
