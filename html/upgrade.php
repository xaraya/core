<?php
/**
 * File: $Id$
 *
 * Quick & dirty import of PN .71x data into Xaraya test sites
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage import
 * @author mikespub <mikespub@xaraya.com>
*/

// initialize the Xaraya core
include 'includes/xarCore.php';
xarCoreInit(XARCORE_SYSTEM_ALL);

if(!xarVarFetch('step','int', $step, NULL, XARVAR_DONT_SET)) {return;}

// pre-fill the module name (if any) for hooks
xarRequestGetInfo();
//Load Table Maintainance API
xarDBLoadTableMaintenanceAPI();

if(!xarSecurityCheck('AdminPanel')) return;
    //include 'modules/privileges/xarsetup.php';


$xarVersion = xarConfigGetVar('System.Core.VersionNum');

$title = xarML('Upgrade');

if (empty($step)) {
    $descr = xarML('Preparing to upgrade from previous Xaraya Version #(1)',$xarVersion);
    // start the output buffer
    ob_start();
?>

<div class="xar-mod-head"><span class="xar-mod-title"><?php echo $title; ?></span></div>
<div class="xar-mod-body"><h2><?php echo $descr; ?></h2><br />
<div style="margin: auto;">
    <form method="POST" action="upgrade.php">
    <p><input type="submit" value="Upgrade Core Tables"></p>

    <input type="hidden" name="step" value="1">
    </form>
    </div>
    </div>

<?php
} else {

   list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();

//Begin Upgrades -- needs to be a switch after this first upgrade.
    switch($xarVersion) {
        case .901:
            xarConfigSetVar('System.Core.VersionNum', '.902');

            // Themes
            xarModSetVar('themes', 'hidecore', 0);
            xarModSetVar('themes', 'selstyle', 'plain');
            xarModSetVar('themes', 'selfilter', 'XARMOD_STATE_ANY');
            xarModSetVar('themes', 'selsort', 'namedesc');
            xarModSetVar('themes', 'SiteFooter', '<a href="http://www.xaraya.com"><img src="modules/base/xarimages/xaraya.gif" alt="Powered by Xaraya" style="border:0px;" /></a>');
            xarModSetVar('themes', 'ShowTemplates', 0);
            xarModSetVar('comments','CollapsedBranches',serialize(array()));


            // Modules

            // expertlist
            $query = "INSERT INTO ".$tables['module_vars']." (xar_id, xar_modid, xar_name, xar_value)
            VALUES (".$dbconn->GenId($tables['module_vars']).",1,'expertlist',0)";
            $result =& $dbconn->Execute($query);
            if(!$result) return;

            // Articles

            // Remove Masks and Instances
            xarRemoveMasks('articles');
            xarRemoveInstances('articles');

            $instances = array(
                               array('header' => 'external', // this keyword indicates an external "wizard"
                                     'query'  => xarModURL('articles', 'admin', 'privileges'),
                                     'limit'  => 0
                                    )
                            );
            xarDefineInstance('articles', 'Article', $instances);

            $query = "SELECT DISTINCT instances.xar_title FROM xar_block_instances as instances LEFT JOIN xar_block_types as types ON types.xar_id = instances.xar_type_id WHERE xar_module = 'articles'";
            $instances = array(
                                array('header' => 'Article Block Title:',
                                        'query' => $query,
                                        'limit' => 20
                                    )
                            );
            xarDefineInstance('articles','Block',$instances);

            xarRegisterMask('ViewArticles','All','articles','Article','All','ACCESS_OVERVIEW');
            xarRegisterMask('ReadArticles','All','articles','Article','All','ACCESS_READ');
            xarRegisterMask('SubmitArticles','All','articles','Article','All','ACCESS_COMMENT');
            xarRegisterMask('EditArticles','All','articles','Article','All','ACCESS_EDIT');
            xarRegisterMask('DeleteArticles','All','articles','Article','All','ACCESS_DELETE');
            xarRegisterMask('AdminArticles','All','articles','Article','All','ACCESS_ADMIN');
            xarRegisterMask('ReadArticlesBlock','All','articles','Block','All','ACCESS_READ');

            // Roles

            $index = array(
               'name'      => 'i_xar_roles_type',
               'fields'    => array('xar_type')
              );
                $query = xarDBCreateIndex($tables['roles'],$index);
                $result =& $dbconn->Execute($query);
                if (!$result) return;

                // username must be unique (for login) + don't allow groupname to be the same either
                $index = array(
                               'name'      => 'i_xar_roles_uname',
                               'fields'    => array('xar_uname'),
                               'unique'    => true
                              );
                $query = xarDBCreateIndex($tables['roles'],$index);
                $result =& $dbconn->Execute($query);
                if (!$result) return;

                // allow identical "real names" here
                $index = array(
                               'name'      => 'i_xar_roles_name',
                               'fields'    => array('xar_name'),
                               'unique'    => false
                              );
                $query = xarDBCreateIndex($tables['roles'],$index);
                $result =& $dbconn->Execute($query);
                if (!$result) return;

                // allow identical e-mail here (???) + is empty for groups !
                $index = array(
                               'name'      => 'i_xar_roles_email',
                               'fields'    => array('xar_email'),
                               'unique'    => false
                              );
                $query = xarDBCreateIndex($tables['roles'],$index);
                $result =& $dbconn->Execute($query);
                if (!$result) return;
            continue;

        case .902:
                xarConfigSetVar('System.Core.VersionNum', '.9.0.3');

                $blockGroupsTable = $tables['block_groups'];

                // Register blocks
                if (!xarModAPIFunc('blocks',
                                   'admin',
                                   'register_block_type',
                                   array('modName'  => 'themes',
                                         'blockType'=> 'syndicate'))) return;

                if (!xarModAPIFunc('blocks', 'admin', 'create_group', array('name'     => 'syndicate',
                                                                            'template' => 'syndicate'))) return;

                $query = "SELECT    xar_id as id
                          FROM      $blockGroupsTable
                          WHERE     xar_name = 'syndicate'";

                // Check for db errors
                $result =& $dbconn->Execute($query);
                if (!$result) return;

                // Freak if we don't get one and only one result
                if ($result->PO_RecordCount() != 1) {
                    $msg = xarML("Group 'syndicate' not found.");
                    xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                   new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
                    return;
                }

                list ($syndicateBlockGroup) = $result->fields;

                $syndicateBlockId= xarModAPIFunc('blocks',
                                                 'admin',
                                                 'block_type_exists',
                                                 array('modName'  => 'themes',
                                                       'blockType'=> 'syndicate'));

                if (!isset($syndicateBlockId) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
                    return;
                }

                if (!xarModAPIFunc('blocks',
                                   'admin',
                                   'create_instance', array('title'    => 'Syndicate',
                                                            'type'     => $syndicateBlockId,
                                                            'group'    => $syndicateBlockGroup,
                                                            'template' => '',
                                                            'state'    => 2))) {
                    return;
                }

            continue;

        case '0.903':  // this is how it's defined in modules/base/xarinit.php
        case '.9.0.3': // this is how it's defined in upgrade.php
                xarConfigSetVar('System.Core.VersionNum', '.9.0.4');

                xarModSetVar('themes', 'SiteTitleSeparator', ' :: ');
                xarModSetVar('themes', 'SiteTitleOrder', 'default');

                $instances = array(
                                   array('header' => 'external', // this keyword indicates an external "wizard"
                                         'query'  => xarModURL('categories', 'admin', 'privileges'),
                                         'limit'  => 0
                                        )
                                  );
                xarDefineInstance('categories', 'Link', $instances);

                xarRegisterMask('ViewCategoryLink','All','categories','Link','All:All:All:All','ACCESS_OVERVIEW');
                xarRegisterMask('SubmitCategoryLink','All','categories','Link','All:All:All:All','ACCESS_COMMENT');
                xarRegisterMask('EditCategoryLink','All','categories','Link','All:All:All:All','ACCESS_EDIT');
                xarRegisterMask('DeleteCategoryLink','All','categories','Link','All:All:All:All','ACCESS_DELETE');

                xarRegisterMask('AdminCategories','All','categories','Category','All:All','ACCESS_ADMIN');

                if (xarModIsAvailable('ratings')) {
                    // when a whole module is removed, e.g. via the modules admin screen
                    // (set object ID to the module name !)
                    if (!xarModRegisterHook('module', 'remove', 'API',
                                            'ratings', 'admin', 'deleteall')) {
                        return false;
                    }
                }

            continue;

        case '.9.0.4': // this is how it's defined in upgrade.php

        /**
         * privileges changes
         */
                xarConfigSetVar('System.Core.VersionNum', '.9.0.5');
                if (!xarModRegisterHook('item', 'waitingcontent', 'GUI',
                                       'articles', 'admin', 'waitingcontent')) {
                    return false;
                }

                $role = xarFindRole('Everybody');
                xarModSetVar('roles', 'everybody', $role->getID());
                $role = xarFindRole('Anonymous');
                xarConfigSetVar('Site.User.AnonymousUID', $role->getID());

                //This creates the new Myself role and makes it a child of Everybody
                xarMakeUser('Myself','myself','myself@xaraya.com','password');
                xarMakeRoleMemberByName('Myself','Everybody');

                // create a couple of new masks
                xarRegisterMask('ViewPanel','All','adminpanels','All','All','ACCESS_OVERVIEW');
                xarRegisterMask('AssignPrivilege','All','privileges','All','All','ACCESS_ADD');
                xarRegisterMask('DeassignPrivilege','All','privileges','All','All','ACCESS_DELETE');

                // This creates the new lock privileges and assigns them to the relevant roles
                xarRegisterPrivilege('GeneralLock','All','empty','All','All','ACCESS_NONE',xarML('A container privilege for denying access to certain roles'));
                xarRegisterPrivilege('LockMyself','All','roles','Roles','Myself','ACCESS_NONE',xarML('Deny access to Myself role'));
                xarRegisterPrivilege('LockEverybody','All','roles','Roles','Everybody','ACCESS_NONE',xarML('Deny access to Everybody role'));
                xarRegisterPrivilege('LockAnonymous','All','roles','Roles','Anonymous','ACCESS_NONE',xarML('Deny access to Anonymous role'));
                xarRegisterPrivilege('LockAdministrators','All','roles','Roles','Administrators','ACCESS_NONE',xarML('Deny access to Administrators role'));
                xarRegisterPrivilege('LockAdministration','All','privileges','Privileges','Administration','ACCESS_NONE',xarML('Deny access to Administration privilege'));
                xarRegisterPrivilege('LockGeneralLock','All','privileges','Privileges','GeneralLock','ACCESS_NONE',xarML('Deny access to GeneralLock privilege'));
                xarMakePrivilegeRoot('GeneralLock');
                xarMakePrivilegeMember('LockMyself','GeneralLock');
                xarMakePrivilegeMember('LockEverybody','GeneralLock');
                xarMakePrivilegeMember('LockAnonymous','GeneralLock');
                xarMakePrivilegeMember('LockAdministrators','GeneralLock');
                xarMakePrivilegeMember('LockAdministration','GeneralLock');
                xarMakePrivilegeMember('LockGeneralLock','GeneralLock');
                xarAssignPrivilege('Administration','Administrators');
                xarAssignPrivilege('GeneralLock','Everybody');
                xarAssignPrivilege('GeneralLock','Administrators');
                xarAssignPrivilege('GeneralLock','Users');

                //Make sure we have the correct stuff for Anonymous and Everybody
                //xarModDelVar('roles', 'Everybody');
                //xarModDelVar('roles', 'Anonymous');

                // Note to self, roles datereg field needs to be changed to a date/time field.

        /**
         * dynamicdata changes
         */

            $dynproptable = xarDBGetSiteTablePrefix() . '_dynamic_properties';
            $query = "UPDATE $dynproptable
                         SET xar_prop_type=3
                       WHERE xar_prop_objectid=2
                         AND xar_prop_name='default'";
            // Check for db errors
            $result =& $dbconn->Execute($query);
            if (!$result) return;

            continue;

        case '.9.0.5':
            xarConfigSetVar('System.Core.VersionNum', '.9.0.6');
            break;
    }

// Fini
    $in_process = xarML('Checking and Correcting');
    $complete = xarML('Upgrades Complete');

// start the output buffer
    ob_start();
?>

<div class="xar-mod-head"><span class="xar-mod-title"><?php echo $title; ?></span></div>
<div class="xar-mod-body"><h2><?php echo $in_process; ?></h2><br />
<div style="margin: auto;">
<?php

    // Upgrade will check to make sure that upgrades in the past have worked, and if not, correct them now.
    $sitePrefix = xarDBGetSiteTablePrefix();
    
    // Drop the admin_wc table and the hooks for the admin panels.
    $table_name['admin_wc'] = $sitePrefix . '_admin_wc'; 

    $upgrade['waiting_content'] = xarModAPIFunc('installer',
                                                'admin',
                                                'CheckTableExists',
                                                array('table_name' => $table_name['admin_wc']));
    if ($upgrade['waiting_content']) {
        echo "$table_name[admin_wc] table still exists, attempting to drop... ";

            // when a module item is created
            xarModUnregisterHook('item', 'create', 'API',
                                 'adminpanels', 'admin', 'createwc');
            xarModUnregisterHook('item', 'update', 'API',
                                 'adminpanels', 'admin', 'deletewc');
            xarModUnregisterHook('item', 'delete', 'API',
                                 'adminpanels', 'admin', 'deletewc');
            xarModUnregisterHook('item', 'remove', 'API',
                                 'adminpanels', 'admin', 'deletewc');

            // Generate the SQL to drop the table using the API
            $query = xarDBDropTable($table_name['admin_wc']);
            $result =& $dbconn->Execute($query);
            if (!$result){ 
                echo "failed</font><br>\r\n";
            } else {
                echo "done!</font><br>\r\n";
            }
    } else {
        echo "$table_name[admin_wc] has been dropped previously, moving to next check. <br />";  
    }

    // Drop the security_privsets table
    $table_name['security_privsets'] = $sitePrefix . '_security_privsets'; 

    $upgrade['security_privsets'] = xarModAPIFunc('installer',
                                                'admin',
                                                'CheckTableExists',
                                                array('table_name' => $table_name['security_privsets']));
    if ($upgrade['security_privsets']) {
        echo "$table_name[security_privsets] table still exists, attempting to drop... ";
        // Generate the SQL to drop the table using the API
        $query = xarDBDropTable($table_name['security_privsets']);
        $result =& $dbconn->Execute($query);
        if (!$result){ 
            echo "failed</font><br>\r\n";
        } else {
            echo "done!</font><br>\r\n";
        }
    } else {
        echo "$table_name[security_privsets] has been dropped previously, moving to next check. <br />";  
    }

    // create and populate the security levels table
    $table_name['security_levels'] = $sitePrefix . '_security_levels'; 

    $upgrade['security_levels'] = xarModAPIFunc('installer',
                                                'admin',
                                                'CheckTableExists',
                                                array('table_name' => $table_name['security_levels']));
    if (!$upgrade['security_levels']) {
        echo "$table_name[security_levels] table does not exist, attempting to create... ";
        $leveltable = $table_name['security_levels'];
        $query = xarDBCreateTable($table_name['security_levels'],
                 array('xar_lid'  => array('type'       => 'integer',
                                          'null'        => false,
                                          'default'     => '0',
                                          'increment'   => true,
                                          'primary_key' => true),
                       'xar_level' => array('type'      => 'integer',
                                          'null'        => false,
                                          'default'     => '0'),
                       'xar_leveltext' => array('type'=> 'varchar',
                                          'size'        => 255,
                                          'null'        => false,
                                          'default'     => ''),
                       'xar_sdescription' => array('type'=> 'varchar',
                                          'size'        => 255,
                                          'null'        => false,
                                          'default'     => ''),
                       'xar_ldescription' => array('type'=> 'varchar',
                                          'size'        => 255,
                                          'null'        => false,
                                          'default'     => '')));
        $result = $dbconn->Execute($query);
        if (!$result){ 
            echo "failed</font><br>\r\n";
        } else {
            echo "done!</font><br>\r\n";
        }

        echo "Attempting to set index and fill $table_name[security_levels]... ";

        $sitePrefix = xarDBGetSiteTablePrefix();
        $index = array('name'      => 'i_'.$sitePrefix.'_security_levels_level',
                       'fields'    => array('xar_level'),
                       'unique'    => FALSE);
        $query = xarDBCreateIndex($leveltable,$index);
        $result =& $dbconn->Execute($query);

        $nextId = $dbconn->GenId($leveltable);
        $query = "INSERT INTO $leveltable (xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription)
                  VALUES ($nextId, -1, 'ACCESS_INVALID', 'Access Invalid', '')";
        $result =& $dbconn->Execute($query);

        $nextId = $dbconn->GenId($leveltable);
        $query = "INSERT INTO $leveltable (xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription)
                  VALUES ($nextId, 0, 'ACCESS_NONE', 'No Access', '')";
        $result =& $dbconn->Execute($query);

        $nextId = $dbconn->GenId($leveltable);
        $query = "INSERT INTO $leveltable (xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription)
                  VALUES ($nextId, 100, 'ACCESS_OVERVIEW', 'Overview Access', '')";
        $result =& $dbconn->Execute($query);

        $nextId = $dbconn->GenId($leveltable);
        $query = "INSERT INTO $leveltable (xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription)
                  VALUES ($nextId, 200, 'ACCESS_READ', 'Read Access', '')";
        $result =& $dbconn->Execute($query);

        $nextId = $dbconn->GenId($leveltable);
        $query = "INSERT INTO $leveltable (xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription)
                  VALUES ($nextId, 300, 'ACCESS_COMMENT', 'Comment Access', '')";
        $result =& $dbconn->Execute($query);

        $nextId = $dbconn->GenId($leveltable);
        $query = "INSERT INTO $leveltable (xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription)
                  VALUES ($nextId, 400, 'ACCESS_MODERATE', 'Moderate Access', '')";
        $result =& $dbconn->Execute($query);

        $nextId = $dbconn->GenId($leveltable);
        $query = "INSERT INTO $leveltable (xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription)
                  VALUES ($nextId, 500, 'ACCESS_EDIT', 'Edit Access', '')";
        $result =& $dbconn->Execute($query);

        $nextId = $dbconn->GenId($leveltable);
        $query = "INSERT INTO $leveltable (xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription)
                  VALUES ($nextId, 600, 'ACCESS_ADD', 'Add Access', '')";
        $result =& $dbconn->Execute($query);

        $nextId = $dbconn->GenId($leveltable);
        $query = "INSERT INTO $leveltable (xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription)
                  VALUES ($nextId, 700, 'ACCESS_DELETE', 'Delete Access', '')";
        $result =& $dbconn->Execute($query);

        $nextId = $dbconn->GenId($leveltable);
        $query = "INSERT INTO $leveltable (xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription)
                  VALUES ($nextId, 800, 'ACCESS_ADMIN', 'Admin Access', '')";
        $result =& $dbconn->Execute($query);

        if (!$result){ 
            echo "failed</font><br>\r\n";
        } else {
            echo "done!</font><br>\r\n";
        }
    } else {
        echo "$table_name[security_levels] already exists, moving to next check. <br />";  
    }

               


?>
<div class="xar-mod-body"><h2><?php echo $complete; ?></h2><br />
Thank you, the upgrades are complete.
</div>
</div>

<?php
}

// catch the output
$return = ob_get_contents();
ob_end_clean();

xarTplSetPageTitle(xarConfigGetVar('Site.Core.SiteName').' :: '.xarML('Upgrade Xaraya'));

//xarTplSetThemeName('Xaraya_Classic');
//xarTplSetPageTemplateName('admin');

// render the page
echo xarTpl_renderPage($return);

// Close the session
xarSession_close();

//$dbconn->Close();

flush();

// Kill the debugger
xarCore_disposeDebugger();

// done
exit;

?>