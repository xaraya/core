<?php
/**
 * Initialise the Authsystem module
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Authsystem module
 * @link http://xaraya.com/index.php/release/42.html
 * @author Jan Schrage, John Cox, Gregor Rothfuss
 */

/**
 * Initialise the Authsystem module
 *
 * @author Jo Dalle Nogare
 * @access public
 * @param none $
 * @returns bool
 */
function authsystem_init()
{
    /* Define privielges */
    xarRegisterPrivilege('AdminAuthsystem','All','authsystem','All','All','ACCESS_ADMIN');

    /* Define Module vars */
 	xarModSetVar('authsystem', 'lockouttime', 15);
	xarModSetVar('authsystem', 'lockouttries', 3);
	xarModSetVar('authsystem', 'uselockout', false);
    /* Define Masks */
    xarRegisterMask('ViewLogin','All','authsystem','Block','login:Login:All','ACCESS_OVERVIEW');
    xarRegisterMask('ViewAuthsystemBlocks','All','authsystem','Block','All','ACCESS_OVERVIEW');
    xarRegisterMask('ViewAuthsystem','All','authsystem','All','All','ACCESS_OVERVIEW');
    xarRegisterMask('EditAuthsystem','All','authsystem','All','All','ACCESS_EDIT');
    xarRegisterMask('AdminAuthsystem','All','authsystem','All','All','ACCESS_ADMIN');
    /* Define and setup privs */
    xarRegisterPrivilege('AdminAuthsystem','All','authsystem','All','All','ACCESS_ADMIN');


  //Do this in installer so we can load this early, and assign the group later after blocks loaded
  /*
   if (!xarModAPIFunc('blocks',
            'admin',
            'register_block_type',
            array('modName' => 'authsystem',
                  'blockType' => 'login'))) return;

     if (!xarModAPIFunc('blocks', 'user', 'get', array('name'  => 'login'))) {
        $rightgroup = xarModAPIFunc('blocks', 'user', 'getgroup', array('name'=> 'right'));
        if (!xarModAPIFunc('blocks', 'admin', 'create_instance',
                           array('title'    => 'Login',
                                 'name'     => 'login',
                                 'type'     => $bid,
                                 'groups'    => array($rightgroup),
                                 'template' => '',
                                 'state'    => 2))) {
            return;
        }
    }
*/
	// Make this the default authentication module
	xarModSetVar('roles', 'defaultauthmodule', xarModGetIDFromName('authsystem'));

    return true;
}
/*
 * We don't have all modules activated at install time
 */
function authsystem_activate()
{
   /* Register blocks */

    $bid = xarModAPIFunc('blocks','admin','register_block_type',
                   array('modName' => 'authsystem',
                         'blockType' => 'login'));
    if (!$bid) return;
    
  return true;
}

/**
 * Upgrade the authsystem module from an old version
 *
 * @access public
 * @param oldVersion $
 * @returns bool
 */
function authsystem_upgrade($oldVersion)
{
    /* Upgrade dependent on old version number */
    switch ($oldVersion) {
        case '0.91.0':
           /* Define privielges */
           xarRegisterPrivilege('AdminAuthsystem','All','authsystem','All','All','ACCESS_ADMIN');

          /* Define Module vars */
          xarModSetVar('authsystem', 'lockouttime', 15);
  	      xarModSetVar('authsystem', 'lockouttries', 3);
	      xarModSetVar('authsystem', 'uselockout', false);
          /* Define Masks */
          xarRegisterMask('ViewLogin','All','authsystem','Block','login:Login:All','ACCESS_OVERVIEW');
          xarRegisterMask('ViewAuthsystemBlocks','All','authsystem','Block','All','ACCESS_OVERVIEW');
          xarRegisterMask('ViewAuthsystem','All','authsystem','All','All','ACCESS_OVERVIEW');
          xarRegisterMask('EditAuthsystem','All','authsystem','All','All','ACCESS_EDIT');
          xarRegisterMask('AdminAuthsystem','All','authsystem','All','All','ACCESS_ADMIN');
  
            //Set the default authmodule if not already set
   	        $isdefaultauth = xarModGetVar('roles','defaultauthmodule');
   	        if (!isset($isdefaultauth)) {
               xarModSetVar('roles', 'defaultauthmodule', xarModGetIDFromName('authsystem'));
            }

           // Get database setup
           $dbconn =& xarDBGetConn();
           $xartable =& xarDBGetTables();
           $systemPrefix = xarDBGetSystemTablePrefix();
           $modulesTable = $systemPrefix .'_modules';
           $modid=xarModGetIDFromName('authsystem');
           // update the modversion class and admin capable
           $query = "UPDATE $modulesTable
                     SET xar_class         = 'Authentication',
                         xar_admin_capable = 1
                     WHERE xar_regid = ?";
           $bindvars = array($modid);
           $result =& $dbconn->Execute($query,$bindvars);

           if (!$result) return;
            //create the blocktype
            $bid = xarModAPIFunc('blocks','admin','register_block_type',
                   array('modName' => 'authsystem',
                         'blockType' => 'login'));
           if (!$bid) return;
       break;

    }
    // Update successful
    return true;
}

/**
 * Delete the authsystem module
 *
 * @access public
 * @param none $
 * @returns bool
 */
function authsystem_delete()
{
    /* Get all available block types for this module */
    $blocktypes = xarModAPIfunc(
        'blocks', 'user', 'getallblocktypes',
        array('module' => 'authsystem')
    );

    /* Delete block types. */
    if (is_array($blocktypes) && !empty($blocktypes)) {
        foreach($blocktypes as $blocktype) {
            $result = xarModAPIfunc(
                'blocks', 'admin', 'delete_type', $blocktype
            );
        }
    }

    /* Remove modvars, instances and masks */
    xarModDelAllVars('authsystem');
    xarRemoveMasks('authsystem');
    xarRemoveInstances('authsystem');

    /* Deletion successful */
    return true;
}

?>