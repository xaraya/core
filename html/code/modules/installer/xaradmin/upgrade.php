<?php
function installer_admin_upgrade()
{
    if(!xarVarFetch('phase','int', $data['phase'], 1, XARVAR_DONT_SET)) {return;}

    // Version information
    $fileversion = XARCORE_VERSION_NUM;
    $dbversion = xarConfigVars::get(null, 'System.Core.VersionNum');
    sys::import('xaraya.version');
    $data['versioncompare'] = xarVersion::compare($fileversion, $dbversion);
    $data['upgradable'] = xarVersion::compare($dbversion, '2.0.0') > 0;

    // Core modules
    $data['coremodules'] = array(
                                42    => 'authsystem',
                                68    => 'base',
                                13    => 'blocks',
                                182   => 'dynamicdata',
                                200   => 'installer',
                                771   => 'mail',
                                1     => 'modules',
                                1098  => 'privileges',
                                27    => 'roles',
                                17    => 'themes',
    );
    $data['versions'] = array(
                                '2.1.0',
    );
    
    if ($data['phase'] == 1) {
        $data['active_step'] = 1;

    } elseif ($data['phase'] == 2) {
        $data['active_step'] = 2;
        if (!Upgrader::loadFile('upgrades/210/main.php')) {
            $data['upgrade']['errormessage'] = Upgrader::$errormessage;
            return $data;
        }
        $data = array_merge($data,main_210());
        
    } elseif ($data['phase'] == 3) {
    } elseif ($data['phase'] == 4) {
    }

    return $data;
}

/**
 * Upgrades necessary since Version 1.0.0 RC1
 * Arranged by versions
 */
function installer_admin_upgrade2()
{
     $thisdata['finishearly']=0;
     $thisdata['xarProduct'] = xarConfigGetVar('System.Core.VersionId');
     $thisdata['xarVersion'] = xarConfigGetVar('System.Core.VersionNum');
     $thisdata['xarRelease'] = xarConfigGetVar('System.Core.VersionSub');

     //Load this early
     xarDBLoadTableMaintenanceAPI();

     $sitePrefix=xarDBGetSiteTablePrefix();
     $systemPrefix=xarDBGetSystemTablePrefix();
     $dbconn =& xarDBGetConn();
/**
 * Version 1.0 Release Upgrades
 * Version 1.0 Release candidate upgrades are also included here
 *             to ensure any version 1.0 installs are upgraded appropriately
 */

    $content = "<p><strong>Checking Site Configuration Variables Structure</strong></p>";

    $cookiename = xarConfigGetVar('Site.Session.CookieName');
    if (!isset($cookiename)) {
        xarConfigSetVar('Site.Session.CookieName', '');
        $content .= "<p>Site.Session.CookieName incorrect, attempting to set.... done!</p>";
    }
    $cookiepath = xarConfigGetVar('Site.Session.CookiePath');
    if (!isset($cookiepath)) {
        xarConfigSetVar('Site.Session.CookiePath', '');
        $content .= "<p>Site.Session.CookiePath incorrect, attempting to set.... done!</p>";
    }
    $cookiedomain = xarConfigGetVar('Site.Session.CookieDomain');
    if (!isset($cookiedomain)) {
        xarConfigSetVar('Site.Session.CookieDomain', '');
        $content .= "<p>Site.Session.CookieDomain incorrect, attempting to set.... done!</p>";
    }
    $referercheck = xarConfigGetVar('Site.Session.RefererCheck');
    if (!isset($referercheck)) {
        xarConfigSetVar('Site.Session.RefererCheck', '');
        $content .= "<p>Site.Session.RefererCheck incorrect, attempting to set.... done!</p>";
    }

    // after 0911, make sure CSS class lib is deployed and css tags are registered
    $content .= "<p><strong>Making sure CSS tags are registered</strong></p>";
    if(!xarModAPIFunc('themes', 'css', 'registercsstags')) {
        $content .= "<p>FAILED to register CSS tags</p>";
    } else {
        $content .= "<p>CSS tags registered successfully, css subsystem is ready to be deployed.</p>";
    }

    // Bug 3164, store locale in ModUSerVar
    xarModSetVar('roles', 'locale', '');

  $content .= "<p><strong>Checking <strong>include/properties</strong> directory for moved DD properties</strong></p>";
    //From 1.0.0rc2 propsinplace was merged and dd propertie began to move to respective modules
    //Check they don't still exisit in the includes directory  bug 4371
    // set the array of properties that have moved
    $ddmoved=array(
        array('Dynamic_AIM_Property.php',1,'Roles'),
        array('Dynamic_Affero_Property.php',1,'Roles'),
        array('Dynamic_Array_Property.php',1,'Base'),
        array('Dynamic_Categories_Property.php',0,'Categories'),
        array('Dynamic_CheckboxList_Property.php',1,'Base'),
        array('Dynamic_CheckboxMask_Property.php',1,'Base'),
        array('Dynamic_Checkbox_Property.php',1,'Base'),
        array('Dynamic_Combo_Property.php',1,'Base'),
        array('Dynamic_CommentsNumberOf_Property.php',0,'Comments'),
        array('Dynamic_Comments_Property.php',0,'Comments'),
        array('Dynamic_CountryList_Property.php',1,'Base'),
        array('Dynamic_DateFormat_Property.php',1,'Base'),
        array('Dynamic_Email_Property.php',1,'Roles'),
        array('Dynamic_ExtendedDate_Property.php',1,'Base'),
        array('Dynamic_FileUpload_Property.php',1,'Roles'),
        array('Dynamic_FloatBox_Property.php',1,'Roles'),
        array('Dynamic_HTMLArea_Property.php',0,'HTMLArea'),
        array('Dynamic_HTMLPage_Property.php',1,'Base'),
        array('Dynamic_HitCount_Property.php',0,'HitCount'),
        array('Dynamic_ICQ_Property.php',1,'Roles'),
        array('Dynamic_ImageList_Property.php',1,'Roles'),
        array('Dynamic_Image_Property.php',1,'Roles'),
        array('Dynamic_LanguageList_Property.php',1,'Base'),
        array('Dynamic_LogLevel_Property.php',0,'Logconfig'),
        array('Dynamic_MSN_Property.php',1,'Roles'),
        array('Dynamic_MultiSelect_Property.php',1,'Base'),
        array('Dynamic_NumberBox_Property.php',1,'Base'),
        array('Dynamic_NumberList_Property.php',1,'Base'),
        array('Dynamic_PassBox_Property.php',1,'Base'),
        array('Dynamic_PayPalCart_Property.php',0,'Paypalsetup'),
        array('Dynamic_PayPalDonate_Property.php',0,'Paypalsetup'),
        array('Dynamic_PayPalNow_Property.php',0,'Paypalsetup'),
        array('Dynamic_PayPalSubscription_Property.php',0,'Paypalsetup'),
        array('Dynamic_RadioButtons_Property.php',1,'Base'),
        array('Dynamic_Rating_Property.php',0,'Ratings'),
        array('Dynamic_Select_Property.php',0,'Base'),
        array('Dynamic_SendToFriend_Property.php',0,'Recommend'),
        array('Dynamic_StateList_Property.php',1,'Base'),
        array('Dynamic_StaticText_Property.php',1,'Base'),
        array('Dynamic_Status_Property.php',0,'Articles'),
        array('Dynamic_TextArea_Property.php',1,'Base'),
        array('Dynamic_TextBox_Property.php',1,'Base'),
        array('Dynamic_TextUpload_Property.php',1,'Base'),
        array('Dynamic_TinyMCE_Property.php',0,'TinyMCE'),
        array('Dynamic_URLIcon_Property.php',1,'Base'),
        array('Dynamic_URLTitle_Property.php',1,'Base'),
        array('Dynamic_URL_Property.php',1,'Roles'),
        array('Dynamic_Upload_Property.php',0,'Uploads'),
        array('Dynamic_Yahoo_Property.php',1,'Roles'),
        array('Dynamic_Calendar_Property.php',1,'Base'),
        array('Dynamic_TColorPicker_Property.php',1,'Base'),
        array('Dynamic_TimeZone_Property.php',1,'Base'),
        array('Dynamic_Module_Property.php',1,'Modules'),
        array('Dynamic_GroupList_Property.php',1,'Roles'),
        array('Dynamic_UserList_Property.php',1,'Roles'),
        array('Dynamic_Username_Property.php',1,'Roles'),
        array('Dynamic_DataSource_Property.php',1,'DynamicData'),
        array('Dynamic_FieldStatus_Property.php',1,'DynamicData'),
        array('Dynamic_FieldType_Property.php',1,'DynamicData'),
        array('Dynamic_Hidden_Property.php',1,'Base'),
        array('Dynamic_ItemID_Property.php',1,'DynamicData'),
        array('Dynamic_ItemType_Property.php',1,'DynamicData'),
        array('Dynamic_Object_Property.php',1,'DynamicData'),
        array('Dynamic_SubForm_Property.php',1,'DynamicData'),
        array('Dynamic_Validation_Property.php',1,'DynamicData')
    );
    //set the array to hold properties that have not moved and should do!
    $ddtomove=array();

    //Check the files in the includes/properties dir against the initial array
    $oldpropdir='includes/properties';
    $var = is_dir($oldpropdir);
    $handle=opendir($oldpropdir);
    $skip_array = array('.','..','SCCS','index.htm','index.html');

    if ($var) {
             while (false !== ($file = readdir($handle))) {
                  // check the  dd file array and add to the ddtomove array if the file exists
                  if (!in_array($file,$skip_array))  {

                     foreach ($ddmoved as $key=>$propname) {
                          if ($file == $ddmoved[$key][0]){
                            $ddtomove[]=$ddmoved[$key];
                           }
                    }
                  }
            }
            closedir($handle);
    }
    if (is_array($ddtomove) && !empty($ddtomove[0])){

        $content .= "<h3 style=\"font:size:large;color:red; font-weigh:bold;\">WARNING!</h3><p>The following DD property files exist in your Xaraya <strong>includes/properties</strong> directory.</p>";
        $content .= "<p>Please delete each of the following and ONLY the following from your <strong>includes/properties</strong> directory as they have now been moved to the relevant module in core, or the 3rd party module concerned.</p>";
        $content .= "<p>Once you have removed the duplicated property files from <strong>includes/properties</strong> please re-run upgrade.php.</p>";

        foreach ($ddtomove as $ddkey=>$ddpropname) {
             if ($ddtomove[$ddkey][1] == 1) {
                $content .= "<p><strong>".$ddtomove[$ddkey][0]."</strong> exits. Please remove it from includes/properties.</p>";
             }else{
                $content .= "<p><strong>".$ddtomove[$ddkey][0]."</strong> is a ".$ddtomove[$ddkey][2]." module property. Please remove it from includes/properties. IF you have ".$ddtomove[$ddkey][2]." installed, check you have the property in the <strong>".strtolower($ddtomove[$ddkey][2])."/xarproperties</strong> directory else upgrade your ".$ddtomove[$ddkey][2]." module.</p>";
             }
        }

        $content .= "<p>REMEMBER! Run upgrade.php again when you delete the above properties from the includes/properties directory.</p>";

        unset($ddtomove);
        $thisdata['content']=$content;
        $thisdata['finishearly']=1;
       return $thisdata;
       // return;
     }else{
         $content .= "<p>Done! All properties have been checked and verified for location!</p>";
    }

/* End Version 1.0.0 Release Updates */

/** Version 1.0.1 Release Upgrades : NONE */

/* Version 1.0.2 Release Upgrades : NONE */

/* Version 1.1.0 Release Upgrades */

    // Set any empty modvars.
    $content .= "<p><strong>Checking Module and Config Variables</strong></p>";

    $modvars[] = array(array('name'    =>  'inheritdeny',
                             'module'  =>  'privileges',
                             'set'     =>  true),
                       array('name'    =>  'tester',
                             'module'  =>  'privileges',
                             'set'     =>  0),
                       array('name'    =>  'test',
                             'module'  =>  'privileges',
                             'set'     =>  false),
                       array('name'    =>  'testdeny',
                             'module'  =>  'privileges',
                             'set'     =>  false),
                       array('name'    =>  'testmask',
                             'module'  =>  'privileges',
                             'set'     =>  'All'),
                       array('name'    =>  'realmvalue',
                             'module'  =>  'privileges',
                             'set'     =>  'none'),
                       array('name'    =>  'realmcomparison',
                             'module'  =>  'privileges',
                             'set'     =>  'exact'),
                       array('name'    =>  'suppresssending',
                             'module'  =>  'mail',
                             'set'     =>  'false'),
                       array('name'    =>  'redirectsending',
                             'module'  =>  'mail',
                             'set'     =>  'exact'),
                       array('name'    =>  'redirectaddress',
                             'module'  =>  'privileges',
                             'set'     =>  ''),
                       array('name'    =>  'displayrolelist',
                             'module'  =>  'roles',
                             'set'     =>  'false'),
                        array('name'    => 'usereditaccount',
                             'module'  =>  'roles',
                             'set'     =>  'true'),
                        array('name'    => 'userlastlogin',
                             'module'  =>  'roles',
                             'set'     =>  ''),
                        array('name'    => 'allowuserhomeedit',
                             'module'  =>  'roles',
                             'set'     =>  'false'),
                        array('name'    => 'setuserhome',
                             'module'  =>  'roles',
                             'set'     =>  'false'),
                        array('name'    => 'setprimaryparent',
                             'module'  =>  'roles',
                             'set'     =>  'false'),
                        array('name'    => 'setpasswordupdate',
                             'module'  =>  'roles',
                             'set'     =>  'false'),
                        array('name'    => 'setuserlastlogin',
                             'module'  =>  'roles',
                             'set'     =>  'false')
                          );
    foreach($modvars as $modvar){
        foreach($modvar as $var){
            $currentvar = xarModGetVar("$var[module]", "$var[name]");
            if (isset($currentvar)){
                if (isset($var['override'])) {
                    xarModSetVar($var['module'], $var['name'], $var['set']);
                    $content .= "<p>$var[module] -> $var[name] has been overridden, proceeding to next check</p>";
                }
                else $content .= "<p>$var[module] -> $var[name] is set, proceeding to next check</p>";
            } else {
                xarModSetVar($var['module'], $var['name'], $var['set']);
                $content .= "<p>$var[module] -> $var[name] empty, attempting to set.... done!</p>";
            }
        }
    }

      // Check the installed privs and masks.
    $content .= "<p><strong>Checking Privilege Structure</strong></p>";

    $upgrade['priv_masks'] = xarMaskExists('ViewPrivileges','privileges','Realm');
    if (!$upgrade['priv_masks']) {
        $content .= "<p>Privileges realm Masks do not exist, attempting to create... done! </p>";

        // create a couple of new masks
        xarRegisterMask('ViewPrivileges','All','privileges','Realm','All','ACCESS_OVERVIEW');
        xarRegisterMask('ReadPrivilege','All','privileges','Realm','All','ACCESS_READ');
        xarRegisterMask('EditPrivilege','All','privileges','Realm','All','ACCESS_EDIT');
        xarRegisterMask('AddPrivilege','All','privileges','Realm','All','ACCESS_ADD');
        xarRegisterMask('DeletePrivilege','All','privileges','Realm','All','ACCESS_DELETE');
    } else {
        $content .= "<p>Privileges realm masks have been created previously, moving to next check. </p>";
    }

    $content .= "<p><strong>Updating Roles and Authsystem for changes in User Login and Authentication</strong></p>";

    //Check for allow registration in existing Roles module
    $allowregistration =xarModGetVar('roles','allowregistration');
    if (isset($allowregistration) && ($allowregistration==1)) {
        //We need to tell user about the new Registration module - let's just warn them for now
        if (!xarModIsAvailable('registration')){
            $content .= "<h2 style=\"color:red;\">WARNING!</h2><p>Your setup indicates you allow User Registration on your site.</p>";
            $content .= "<p>Handling of User Registration has changed in this version. Please install and activate the <strong>Registration</strong> module to continue User Registration on your site.</p>";
            $content .= "<p>You should also remove any existing login blocks and install the Registration module Login block if you wish to include a Registration link in the block.</p>";
        }
    }

    //we need to check the login block is the Authsystem login block, not the Roles
    //As the block is the same we could just change the type id of any login block type.
    $blocktypeTable = $systemPrefix .'_block_types';
    $blockinstanceTable = $systemPrefix .'_block_instances';
    $blockproblem=array();
       //Get the block type id of the existing block type
        $query = "SELECT xar_id,
                         xar_type,
                         xar_module
                         FROM $blocktypeTable
                 WHERE xar_type=? and xar_module=?";
        $result =& $dbconn->Execute($query,array('login','roles'));
        list($blockid,$blocktype,$module)= $result->fields;
        $blocktype = array('id' => $blockid,
                           'blocktype' => $blocktype,
                           'module'=> $module);

        if (is_array($blocktype) && $blocktype['module']=='roles') {

            $blockid=$blocktype['id'];
            //set the module to authsystem and it can be used for the existing block instance
            $query = "UPDATE $blocktypeTable
                      SET xar_module = ?
                      WHERE xar_id=?";
            $bindvars=array('authsystem',$blockid);
            $result =& $dbconn->Execute($query,$bindvars);

        }


    // Define and setup privs that may not be registered
    if (!xarPrivExists('AdminAuthsystem')) {
        xarRegisterPrivilege('AdminAuthsystem','All','authsystem','All','All','ACCESS_ADMIN');
    }
    if (!xarPrivExists('ViewAuthsystem')) {
        xarRegisterPrivilege('ViewAuthsystem','All','authsystem','All','All','ACCESS_OVERVIEW');
    }
    xarUnregisterMask('ViewLogin');
    xarRegisterMask('ViewLogin','All','authsystem','Block','login:Login:All','ACCESS_OVERVIEW');
    xarRegisterMask('ViewAuthsystemBlocks','All','authsystem','Block','All','ACCESS_OVERVIEW');
    xarRegisterMask('ViewAuthsystem','All','authsystem','All','All','ACCESS_OVERVIEW');
    xarRegisterMask('EditAuthsystem','All','authsystem','All','All','ACCESS_EDIT');
    xarRegisterMask('AdminAuthsystem','All','authsystem','All','All','ACCESS_ADMIN');
     //Register a mask to maintain backward compatibility - this mask is used a lot as a hack for admin perm check in themes
    xarRegisterMask('AdminPanel','All','base','All','All','ACCESS_ADMIN');
    // Test for existance of privilege already assigned to priv group
    // If not add it
    $privileges = new xarPrivileges();
    $thispriv= $privileges->findPrivilege('ViewAuthsystem');
    $parents= $thispriv->getparents();
    $casual=false;
    $readcore=false;
    foreach ($parents as $parent) {
         if ($parent->getName() == 'CasualAccess') $casual=true;
         if ($parent->getName() == 'ReadNonCore') $readcore=true;
    }
    if (xarPrivExists('CasualAccess') && !$casual)  {
       xarMakePrivilegeMember('ViewAuthsystem','CasualAccess');
    }elseif (xarPrivExists('ReadNonCore') && !$readcore) {
        xarMakePrivilegeMember('ViewAuthsystem','ReadNonCore');
    }

      // Define Module vars
     xarModSetVar('authsystem', 'lockouttime', 15);
    xarModSetVar('authsystem', 'lockouttries', 3);
    xarModSetVar('authsystem', 'uselockout', false);
    xarModSetVar('roles', 'defaultauthmodule', xarModGetIDFromName('authsystem'));


    $content .= "<p><strong>Removing Adminpanels module and move functions to other  modules</strong></p>";
    // Adminpanels module overviews modvar is deprecated
    // Move off Adminpanels dashboard modvar to Themes module

    //Check that we have waiting content hooks activated for Articles and adminpanels
    //if so set them now for Base
    if (xarModIsHooked('articles','adminpanels')) {
        //set it to Base now
         xarModAPIFunc('modules','admin','enablehooks',
                       array('callerModName' => 'base', 'hookModName' => 'articles'));
    }
    //Safest way is to just set the dash off for now
    xarModSetVar('themes','usedashboard',false);
    xarModSetVar('themes','dashtemplate','admin');

    $table_name['admin_menu']=$sitePrefix . '_admin_menu';
    $upgrade['admin_menu'] = xarModAPIFunc('installer',
                                                'admin',
                                                'CheckTableExists',
                                                array('table_name' => $table_name['admin_menu']));
    //Let's remove the now unused admin menu table
    if ($upgrade['admin_menu']) {
        $adminmenuTable = $systemPrefix .'_admin_menu';
        $query = xarDBDropTable($adminmenuTable);
        $result = &$dbconn->Execute($query);
     }

    //We need to upgrade the blocks, and as the block is the same we could just change the type id of any login.
    $blocktypeTable = $systemPrefix .'_block_types';
    $blockinstanceTable = $systemPrefix .'_block_instances';
    $newblocks=array('waitingcontent','adminmenu');
    $blockproblem=array();
    foreach ($newblocks as $newblock) {
        // We don't need to register new block = just change the existing block

        //Get the ID of the old block type
        $query = "SELECT xar_id,
                         xar_type,
                         xar_module
                         FROM $blocktypeTable
                 WHERE xar_type=? and xar_module=?";
        $result =& $dbconn->Execute($query,array($newblock,'adminpanels'));

        if ($result) {
            list($blockid,$blocktype,$module)= $result->fields;
            //update the module name in the block with that id to 'base'
            $blocktype = array('id' => $blockid,
                           'blocktype' => $blocktype,
                           'module'=> $module);

            if (is_array($blocktype) && $blocktype['module']=='adminpanels') {
               $blockid=$blocktype['id'];
               //set the module to base
               $query = "UPDATE $blocktypeTable
                         SET xar_module = ?
                         WHERE xar_id=?";
               $bindvars=array('base',$blockid);
               $result =& $dbconn->Execute($query,$bindvars);

               if (($newblock='waitingcontent') && isset($blockid)) {
                   //We need to disable existing hooks and enable new ones - but which :)
                   $hookTable = $systemPrefix .'_hooks';
                   $query = "UPDATE $hookTable
                             SET xar_smodule = 'base'
                             WHERE xar_action=? AND xar_smodule=?";
                    $bindvars = array('base','waitingcontent','adminpanels');
                    //? no execute here?
               }
            }
            //Remove the original block
            if (!xarModAPIFunc('blocks','admin','unregister_block_type',
                       array('modName'  => 'adminpanels',
                             'blockType'=> $newblock))) {
              $blockproblem[]=1;
            }

        }
      }
    if (count($blockproblem) >0) {
        $content .= "<p><span style=\"color:red;\">WARNING!</span> There was a problem in updating Waiting Content and Adminpanels menu block to Base blocks. Please check!</p>";
    }else {
        $content .= "<p>Done! Waiting content and Admin Menu block updated in Base module!</p>";
    }

    $content .= "<p>Removing unused adminpanel module variables</p>";
    $delmodvars[] = array(array('name'    =>  'showlogout',
                               'module'  =>  'adminpanels'),
                         array('name'    =>  'dashboard',
                               'module'  =>  'adminpanels'),
                         array('name'    =>  'overview',
                               'module'  =>  'adminpanels'),
                         array('name'    =>  'menustyle',
                               'module'  =>  'adminpanels')
                         );

     foreach($delmodvars as $delmodvar){
        foreach($delmodvar as $var){
            $currentvar = xarModGetVar("$var[module]", "$var[name]");
            if (!isset($currentvar)){
                $content .= "<p>$var[module] -> $var[name] is deleted, proceeding to next check</p>";
            } else {
                xarModDelVar($var['module'], $var['name']);
                $content .= "<p>$var[module] -> $var[name] has value, attempting to delete.... done!</p>";
            }
        }
    }

    // Remove Masks and Instances
    xarRemoveMasks('adminpanels');
    xarRemoveInstances('adminpanels');

    //Remove the Adminpanel module entry
    $aperror=0;
    $moduleTable = $systemPrefix .'_modules';
    $moduleStatesTable=$systemPrefix .'_module_states';
    $adminpanels='adminpanels';
    $query = "SELECT xar_name,
                     xar_regid
              FROM $moduleTable
              WHERE xar_name = ?";
    $result = &$dbconn->Execute($query,array($adminpanels));
    list($name, $adminregid) = $result->fields;
    if (!$result) $aperror=1;
    if (isset($adminregid) and $aperror<=0) {
        $query = "DELETE FROM $moduleTable WHERE xar_regid = ?";
        $result = &$dbconn->Execute($query,array($adminregid));
        if (!$result) $aperror=1;
        $query = "DELETE FROM $moduleStatesTable WHERE xar_regid = ?";
        $result = &$dbconn->Execute($query,array($adminregid));
        if (!$result) $aperror=1;
    }
    if ($aperror<=0) {
          $content .= "<p>Done! Adminpanel module has been removed!</p>";
    }else {
         $content .= "<p><span style=\"color:red;\">WARNING!</span> There was a problem removing Adminpanel module from the module listing.You may wish to remove it manually from your module listing after you log in.</p>";
    }

/* End of Version 1.1.0 Release Upgrades */

/* Version 1.1.1 Release Upgrades */
    xarModSetVar('themes', 'adminpagemenu', 1); //New variables to switch admin in page menus (tabs) on and off
    xarModSetVar('privileges', 'inheritdeny', true); //Was not set in privileges activation in 1.1, isrequired, maybe missing in new installs
    xarModSetVar('roles', 'requirevalidation', true); //reuse this older var for user email changes, this validation is separate to registration validation
/* End of Version 1.1.1 Release Upgrades */


/* Version 1.1.2 Release Upgrades */
    //Module Upgrades should take care of most
    //Need to convert privileges but only if we decide to update the current Blocks module functions' privilege checks

    //We are allowing setting var that is reliably referenced for the xarMLS calculations (instead of using a variably named DD property which was the case)
    // This var becomes one of the roles 'duv' modvars
    xarModSetVar('roles', 'setusertimezone',false); //new modvar - let's make sure it's set
    xarModDelVar('roles', 'settimezone');//this is no longer used, be more explicit and user setusertimezone
    xarModSetVar('roles', 'usertimezone',''); //new modvar - initialize it
    xarModSetVar('roles', 'usersendemails', false); //old modvar returns. Let's make sure it's set false as it allows users to send emails

    //Ensure that registration module is set as default if it is installed,
    // if it is active and the default is currently not set
    $defaultregmodule= xarModGetVar('roles','defaultregmodule');
    if (!isset($defaultregmodule)) {
        if (xarModIsAvailable('registration')) {
            xarModSetVar('roles','defaultregmodule',xarModGetIDFromName('registration'));
        }
    }

    // Ensure base timesince tag handler is added
    xarTplUnregisterTag('base-timesince');
    xarTplRegisterTag('base', 'base-timesince', array(),
                      'base_userapi_handletimesincetag');
/* End 1.1.2 Release Upgrades */


/* Version 1.1.3 Release Upgrades */
    //move the disallowedemails back to roles rather than in Registration with disallowed username and ips
    //Check to see if the registration var exists and is not empty
    $existingvar = xarModGetVar('registration','disallowedemails');
    $existingregdisallowed = isset($existingvar) ? unserialize($existingvar): '';
    //but what if this is an old install and the roles equivalent is defined and not empty?
    $rolesisallowedvar = xarModGetVar('roles','disallowedemails');
    $existingrolesdisallowed = isset($rolesisallowedvar) ? unserialize($rolesisallowedvar): '';
    //Always take the registraiton var as it will be most recent if it exists and is not empty
    if (!empty($existingdisallowed)) {
       $emails = $existingdisallowed;
    } elseif (!empty($existingrolesdisallowed)) {
       $emails = $existingrolesdisallowed;
    }else {
        $emails = "none@none.com\npresident@whitehouse.gov";
    }
    $disallowedemails = serialize($emails);
    
    xarModSetVar('roles', 'disallowedemails', $disallowedemails);
/* End 1.1.3 Release Upgrades */

/* Version 1.1.4 Release Upgrades */
    //Overwriting masks with component 'All' with 'Roles', bug 6161
    xarRegisterMask('ViewRoles',  'All', 'roles', 'Roles', 'All', 'ACCESS_OVERVIEW');
    xarRegisterMask('ReadRole',   'All', 'roles', 'Roles', 'All', 'ACCESS_READ');
    xarRegisterMask('EditRole',   'All', 'roles', 'Roles', 'All', 'ACCESS_EDIT');
    xarRegisterMask('AddRole',    'All', 'roles', 'Roles', 'All', 'ACCESS_ADD');
    xarRegisterMask('DeleteRole', 'All', 'roles', 'Roles', 'All', 'ACCESS_DELETE');
    xarRegisterMask('AdminRole',  'All', 'roles', 'Roles', 'All', 'ACCESS_ADMIN');
/* End 1.1.4 Release Upgrades */


    $thisdata['content']=$content;
    $thisdata['phase'] = 2;
    $thisdata['phase_label'] = xarML('Step Two');

    return $thisdata;
}
// Miscellaneous upgrade functions that always run each upgrade
// Version independent
function installer_admin_upgrade3()
{
    $content='';
    $thisdata['xarProduct'] = xarConfigGetVar('System.Core.VersionId');
    $thisdata['xarVersion'] = xarConfigGetVar('System.Core.VersionNum');
    $thisdata['xarRelease'] = xarConfigGetVar('System.Core.VersionSub');
    $content='';

    // Set Config Vars - add those that need to be set each upgrade here.
    $roleanon = xarFindRole('Anonymous');
    $configvars[] = array(
                           array('name'    =>  'System.Core.VersionNum',
                                 'set'     =>  XARCORE_VERSION_NUM));
    $content .=  "<h3><strong>Updating Required Configuration Variables</strong></h3>";
    foreach($configvars as $configvar){
        foreach($configvar as $var){
            $currentvar = xarConfigGetVar("$var[name]");
            if ($currentvar == $var['set']){
                $content .= "<p>$var[name] is set, proceeding to next check</p>";
            } else {
                xarConfigSetVar($var['name'], $var['set']);
                $content .= "<p>$var[name] incorrect, attempting to set.... done!</p>";
            }
        }
    }
  // Bug 630, let's throw the reminder back up after upgrade.
    if (!xarModAPIFunc('blocks', 'user', 'get', array('name' => 'reminder'))) {
        $varshtml['html_content'] = 'Please delete install.php and upgrade.php from your webroot.';
        $varshtml['expire'] = time() + 7*24*60*60; // 7 days

        $htmlBlockType = xarModAPIFunc(
            'blocks', 'user', 'getblocktype',
            array('module' => 'base', 'type' => 'html')
        );

        if (empty($htmlBlockType) && xarCurrentErrorType() != XAR_NO_EXCEPTION) {
            return;
        }

        // Get the first available group ID, and assume that will be
        // visible to the administrator.
        $allgroups = xarModAPIFunc(
            'blocks', 'user', 'getallgroups',
            array('order' => 'id')
        );
        $topgroup = array_shift($allgroups);

        if (!xarModAPIFunc(
            'blocks', 'admin', 'create_instance',
            array(
                'title'    => 'Reminder',
                'name'     => 'reminder',
                'content'  => $varshtml,
                'type'     => $htmlBlockType['tid'],
                'groups'   => array(array('gid' => $topgroup['gid'])),
                'state'    => 2))) {
            return;
        }
    } // End bug 630


    // Flush the property cache, so on upgrade all proptypes
    // are properly set in the database.
    $content .=  "<h3><strong>Flushing the property cache</strong></h3>";
    if(!xarModAPIFunc('dynamicdata','admin','importpropertytypes', array('flush' => true))) {
        $content .=  "<p>WARNING: Flushing property cache failed</p>";
    } else {
        $content .=  "<p>Success! Flushing property cache complete</p>";
    }

    $thisdata['content']=$content;
    $thisdata['phase'] = 3;
    $thisdata['phase_label'] = xarML('Step Three');

    return $thisdata;
}
function installer_admin_upgrade4()
{
    $content='';
    $thisdata['xarProduct'] = xarConfigGetVar('System.Core.VersionId');
    $thisdata['xarVersion'] = xarConfigGetVar('System.Core.VersionNum');
    $thisdata['xarRelease'] = xarConfigGetVar('System.Core.VersionSub');
    $thisdata['content']=$content;
    $thisdata['phase'] = 4;
    $thisdata['phase_label'] = xarML('Step Four');

    return $thisdata;
}
?>
