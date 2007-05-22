<?php
/**
 * Initialise the Authsystem module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage authsystem
 * @link http://xaraya.com/index.php/release/42.html
 * @author Jan Schrage, John Cox, Gregor Rothfuss
 */

/**
 * Initialise the Authsystem module
 *
 * @author Jo Dalle Nogare <jojodee@xaraya.com>
 * @access public
 * @return bool
 */
function authsystem_init()
{
    /* This init function brings authsystem to version 0.91; run the upgrades for the rest of the initialisation */
    return authsystem_upgrade('0.91');
}
/*
 * We don't have all modules activated at install time
 */
function authsystem_activate()
{
    xarRegisterPrivilege('AdminAuthsystem','All','authsystem','All','All','ACCESS_ADMIN');
    xarRegisterPrivilege('ViewAuthsystem','All','authsystem','All','All','ACCESS_OVERVIEW');
    xarMakePrivilegeRoot('AdminAuthsystem');
    xarMakePrivilegeRoot('ViewAuthsystem');

    xarRegisterMask('ViewLogin','All','authsystem','Block','login:Login:All','ACCESS_OVERVIEW');
    xarRegisterMask('ViewAuthsystemBlocks','All','authsystem','Block','All','ACCESS_OVERVIEW');
    xarRegisterMask('ViewAuthsystem','All','authsystem','All','All','ACCESS_OVERVIEW');
    xarRegisterMask('EditAuthsystem','All','authsystem','All','All','ACCESS_EDIT');
    xarRegisterMask('AdminAuthsystem','All','authsystem','All','All','ACCESS_ADMIN');

    /* Define Module vars */
    xarModVars::set('authsystem', 'lockouttime', 15);
    xarModVars::set('authsystem', 'lockouttries', 3);
    xarModVars::set('authsystem', 'uselockout', false);

    return true;
}

/**
 * Upgrade the authsystem module from an old version
 *
 * @access public
 * @param oldVersion $
 * @return bool true on success of upgrade
 */
function authsystem_upgrade($oldVersion)
{
    /* Upgrade dependent on old version number */
    switch ($oldVersion) {
        case '0.91':
        case '0.91.0':

           //Set the default authmodule if not already set
           $isdefaultauth = xarModVars::get('roles','defaultauthmodule');
           if (!isset($isdefaultauth) || !is_integer($isdefaultauth)) {
               xarModVars::get('roles', 'defaultauthmodule', xarMod::getID('authsystem'));
           }

           $dbconn =& xarDB::getConn();
           $xartable =& xarDB::getTables();
           $modulesTable = xarDB::getPrefix() .'_modules';
           $modid = xarModGetIDFromName('authsystem');
           // update the modversion class and admin capable
           $query = "UPDATE $modulesTable SET class=?, admin_capable=?
                     WHERE regid = ?";
           $bindvars = array('Authentication',1,$modid);
           $result = $dbconn->Execute($query,$bindvars);

           // Create the login block
           if (!$result) return;
            //create the blocktype
            $bid = xarModAPIFunc('blocks','admin','register_block_type',
                   array('modName' => 'authsystem',
                         'blockType' => 'login'));
           if (!$bid) return;

        case '1.0.0': // current version

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
 * @return bool true on success of deletion
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
