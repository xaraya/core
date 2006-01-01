<?php

function vendors_init()
{

# --------------------------------------------------------
#
# Set up masks
#
    xarRegisterMask('ViewVendors','All','vendors','All','All','ACCESS_OVERVIEW');
    xarRegisterMask('AdminVendors','All','vendors','All','All','ACCESS_ADMIN');

# --------------------------------------------------------
#
# Set up privileges
#
    xarRegisterPrivilege('AdminVendors','All','vendors','All','All','ACCESS_ADMIN');

# --------------------------------------------------------
#
# Set up modvars
#
    xarModSetVar('vendors', 'itemsperpage', 20);

# --------------------------------------------------------
#
# Set up hooks
#
    // This is a GUI hook for the roles module that enhances the roles profile page
    if (!xarModRegisterHook('item', 'usermenu', 'GUI',
            'vendors', 'user', 'usermenu')) {
        return false;
    }

    xarModAPIFunc('modules', 'admin', 'enablehooks',
        array('callerModName' => 'roles', 'hookModName' => 'vendors'));

# --------------------------------------------------------
#
# Delete block details for this module (for now)
#
    $blocktypes = xarModAPIfunc(
        'blocks', 'user', 'getallblocktypes',
        array('module' => 'vendors')
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
            array('modName' => 'vendors',
                'blockType' => 'manufacturer_info'))) return;

    if (!xarModAPIFunc('blocks',
            'admin',
            'register_block_type',
            array('modName' => 'vendors',
                'blockType' => 'manufacturers'))) return;

# --------------------------------------------------------
#
# Register block instances
#
// Put a manufacturers block in the 'right' blockgroup
/*    $type = xarModAPIFunc('blocks', 'user', 'getblocktype', array('module' => 'vendors', 'type'=>'manufacturers'));
    $rightgroup = xarModAPIFunc('blocks', 'user', 'getgroup', array('name'=> 'right'));
    $bid = xarModAPIFunc('blocks','admin','create_instance',array('type' => $type['tid'],
                                                                  'name' => 'productsmanufacturers',
                                                                  'state' => 0,
                                                                  'groups' => array($rightgroup)));
*/
# --------------------------------------------------------
#
# Set extensions
#

    $ice_objects = array('ice_suppliers');

    // Treat destructive right now
    $existing_objects  = xarModApiFunc('dynamicdata','user','getobjects');
    foreach($existing_objects as $objectid => $objectinfo) {
        if(in_array($objectinfo['name'], $ice_objects)) {
            // KILL
            if(!xarModApiFunc('dynamicdata','admin','deleteobject', array('objectid' => $objectid))) return;
        }
    }

    $objects = unserialize(xarModGetVar('commerce', 'ice_objects'));
    foreach($ice_objects as $ice_object) {
        $def_file = 'modules/vendors/xardata/'.$ice_object.'-def.xml';
        $dat_file = 'modules/vendors/xardata/'.$ice_object.'-data.xml';

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

	$parent = xarFindRole('CommerceRoles');
	$role = xarFindRole('Suppliers');
	if (empty($role)) {
		$new = array('name' => 'Suppliers',
					 'itemtype' => ROLES_GROUPTYPE,
					 'parentid' => $parent->getID(),
					);
		$uid1 = xarModAPIFunc('roles','admin','create',$new);
	}
	$role = xarFindRole('Manufacturers');
	if (empty($role)) {
		$new = array('name' => 'Manufacturers',
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
    $info = xarModGetInfo(xarModGetIDFromName('vendors'));
    $modules[$info['name']] = $info['regid'];
    $result = xarModSetVar('commerce', 'ice_modules', serialize($modules));

    return true;
}

function vendors_upgrade()
{
    return true;
}

function vendors_delete()
{
    // Load table maintenance API
    xarDBLoadTableMaintenanceAPI();

    // Generate the SQL to drop the table using the API
    $prefix = xarDBGetSiteTablePrefix();
    $table = $prefix . "_vendors";
    $query = xarDBDropTable($table);
    if (empty($query)) return; // throw back

    // Delete the DD objects created by this module
	$ice_objects = unserialize(xarModGetVar('commerce','ice_objects'));
	if (isset($ice_objects['ice_suppliers']))
		$result = xarModAPIFunc('dynamicdata','admin','deleteobject',array('objectid' => $ice_objects['ice_suppliers']));
	if (isset($ice_objects['ice_manufacturers']))
		$result = xarModAPIFunc('dynamicdata','admin','deleteobject',array('objectid' => $ice_objects['ice_manufacturers']));

	// Purge all the roles created by this module
	$role = xarFindRole('Suppliers');
	$descendants = $role->getDescendants();
	foreach ($descendants as $item)
		if (!$item->purge()) return;
	if (!$role->purge()) return;

	$role = xarFindRole('Manufacturers');
	$descendants = $role->getDescendants();
	foreach ($descendants as $item)
		if (!$item->purge()) return;
	if (!$role->purge()) return;

    // Remove Masks and Instances
    xarRemoveMasks('vendors');
    xarRemoveInstances('vendors');

    // Remove Modvars
    xarModDelAllVars('vendors');

    // Remove from the list of commerce modules
    $modules = unserialize(xarModGetVar('commerce', 'ice_modules'));
    unset($modules['vendors']);
    $result = xarModSetVar('commerce', 'ice_modules', serialize($modules));

    return true;
}

?>
