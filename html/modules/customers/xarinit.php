<?php

function customers_init()
{

# --------------------------------------------------------
#
# Set up masks
#
    xarRegisterMask('ViewCustomers','All','customers','All','All','ACCESS_OVERVIEW');
    xarRegisterMask('AdminCustomers','All','customers','All','All','ACCESS_ADMIN');

# --------------------------------------------------------
#
# Set up privileges
#
    xarRegisterPrivilege('AdminCustomers','All','customers','All','All','ACCESS_ADMIN');

# --------------------------------------------------------
#
# Set up modvars
#
    xarModSetVar('customers', 'itemsperpage', 20);

# --------------------------------------------------------
#
# Set up hooks
#
    // This is a GUI hook for the roles module that enhances the roles profile page
    if (!xarModRegisterHook('item', 'usermenu', 'GUI',
            'customers', 'user', 'usermenu')) {
        return false;
    }

    xarModAPIFunc('modules', 'admin', 'enablehooks',
        array('callerModName' => 'roles', 'hookModName' => 'customers'));

# --------------------------------------------------------
#
# Delete block details for this module (for now)
#
    $blocktypes = xarModAPIfunc(
        'blocks', 'user', 'getallblocktypes',
        array('module' => 'customers')
    );

    // Delete block types.
    if (is_array($blocktypes) && !empty($blocktypes)) {
        foreach($blocktypes as $blocktype) {
            $result = xarModAPIfunc(
                'blocks', 'admin', 'delete_type', $blocktype
            );
        }
    }

# --------------------------------------------------------
#
# Register block types
#
    if (!xarModAPIFunc('blocks',
            'admin',
            'register_block_type',
            array('modName' => 'customers',
                'blockType' => 'customers_status'))) return;


# --------------------------------------------------------
#
# Set extensions
#

    $ice_objects = array('ice_customers','ice_customer_groups');

    // Treat destructive right now
    $existing_objects  = xarModApiFunc('dynamicdata','user','getobjects');
    foreach($existing_objects as $objectid => $objectinfo) {
        if(in_array($objectinfo['name'], $ice_objects)) {
            // KILL
            if(!xarModApiFunc('dynamicdata','admin','deleteobject', array('objectid' => $objectid))) return;
        }
    }

	$objects = unserialize(xarModGetVar('commerce','ice_objects'));
    foreach($ice_objects as $ice_object) {
        $def_file = 'modules/customers/xardata/'.$ice_object.'-def.xml';
        $dat_file = 'modules/customers/xardata/'.$ice_object.'-data.xml';

        $objectid = xarModAPIFunc('dynamicdata','util','import', array('file' => $def_file));
        if (!$objectid) continue;
        else $objects[$ice_object] = $objectid;
        // Let data import be allowed to be empty
        if(file_exists($dat_file)) {
            // And allow it to fail for now
            xarModAPIFunc('dynamicdata','util','import', array('file' => $dat_file,'keepitemid' => true));
        }
    }

	xarModSetVar('commerce','ice_objects',serialize($objects));

	$role = xarFindRole('Customers');
	if (empty($role)) {
		$parent = xarFindRole('CommerceRoles');
		$new = array('name' => 'Customers',
					 'itemtype' => ROLES_GROUPTYPE,
					 'parentid' => $parent->getID(),
					);
		$uid1 = xarModAPIFunc('roles','admin','create',$new);
	}

# --------------------------------------------------------
#
# Add this module to the list of installed commerce suite modules
#
    $modules = unserialize(xarModGetVar('commerce', 'ice_modules'));
    $info = xarModGetInfo(xarModGetIDFromName('customers'));
    $modules[$info['name']] = $info['regid'];
    $result = xarModSetVar('commerce', 'ice_modules', serialize($modules));

    return true;
}

function customers_upgrade()
{
    return true;
}

function customers_delete()
{
    // Load table maintenance API
    xarDBLoadTableMaintenanceAPI();

    // Generate the SQL to drop the table using the API
    $prefix = xarDBGetSiteTablePrefix();
    $table = $prefix . "_customers";
    $query = xarDBDropTable($table);
    if (empty($query)) return; // throw back

    // Delete the DD objects created by this module
	$ice_objects = unserialize(xarModGetVar('commerce','ice_objects'));
	if (isset($ice_objects['ice_customers']))
		$result = xarModAPIFunc('dynamicdata','admin','deleteobject',array('objectid' => $ice_objects['ice_customers']));

	// Purge all the roles created by this module
	$role = xarFindRole('Customers');
	$descendants = $role->getDescendants();
	foreach ($descendants as $item)
		if (!$item->purge()) return;
	if (!$role->purge()) return;

    // Remove Masks and Instances
    xarRemoveMasks('customers');
    xarRemoveInstances('customers');

    // Remove Modvars
    xarModDelAllVars('customers');

    // Remove from the list of commerce modules
    $modules = unserialize(xarModGetVar('commerce', 'ice_modules'));
    unset($modules['customers']);
    $result = xarModSetVar('commerce', 'ice_modules', serialize($modules));

    return true;
}

?>
