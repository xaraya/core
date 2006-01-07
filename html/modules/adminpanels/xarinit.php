<?php
/**
 * Administration System
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage adminpanels module
 * @author Andy Varganov <andyv@xaraya.com>
 */


/**
 * Initialise the adminpanels module
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @param   none
 * @return  true on success or void or false on failure
 * @throws  'DATABASE_ERROR'
 * @todo    nothing
*/
function adminpanels_init()
{
    // Register blocks
    if (!xarModAPIFunc('blocks','admin','register_block_type',
                       array('modName'  => 'adminpanels',
                             'blockType'=> 'adminmenu'))) return;

    if (!xarModAPIFunc('blocks', 'admin', 'register_block_type',
                       array('modName'  => 'adminpanels',
                             'blockType'=> 'waitingcontent'))) return;


    // Set module variables
    xarModSetVar('adminpanels','menuposition', 'l');
    xarModSetVar('adminpanels','menustyle', 'bycat');
    xarModSetVar('adminpanels','showontop', 1);
    xarModSetVar('adminpanels','showhelp', 1);
    xarModSetVar('adminpanels','marker', '[x]');
    
    // after version 1.2.0
    xarModSetVar('adminpanels','showlogout', 1);
    xarModSetVar('adminpanels','showmarker', 0);
    
    // Initialisation successful
    return true;
}

/**
 * Upgrade the adminpanels module from an old version
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @param   $oldversion
 * @return  true on success or false on failure
 * @throws  no exceptions
 * @todo    nothing
*/
function adminpanels_upgrade($oldversion)
{
        
    // Upgrade dependent on old version number
    switch($oldversion) {
        case '1.0': // first ever version as string
        case  1.0:  // first ever version as float
        case '1.2.0':
            // sort out modvars, remove unused and add new ones
            if(!xarModGetVar('adminpanels','showlogout')){
                xarModSetVar('adminpanels','showlogout', 1);
            }
            if(xarModGetVar('adminpanels','showold')){
                xarModDelVar('adminpanels','showold');
                xarModSetVar('adminpanels','showmarker', 0);
            }
        case '1.2.1':
            // Remove redundant modvars.
            xarModDelVar('adminpanels', 'showontop');
            xarModDelVar('adminpanels', 'menuposition');
    }
    return true;
}

/**
 * Delete the adminpanels module
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @param   no parameters
 * @return  true on success or false on failure
 * @todo    restore the default behaviour prior to 1.0 release
*/
function adminpanels_delete()
{
  //this module cannot be removed via gui
  return false;
}

?>
