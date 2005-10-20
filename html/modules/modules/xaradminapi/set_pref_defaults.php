<?php
/**
 * File: $Id$
 *
 * Reset admin preferences to default module preferences
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @subpackage modules module
 * @author Xaraya Team 
 */
/**
 * reset admin preferences to default module preferences
 *
 * @access public
 * @param none
 * @returns bool
 * @return true on success, false on failure
 * @raise BAD_PARAM
 */
function modules_adminapi_set_pref_defaults()
{
    // no beating around the bush here
    if(xarModGetUserVar('modules', 'hidecore'))     xarModDelUserVar('modules', 'hidecore');
    if(xarModGetUserVar('modules', 'regen'))        xarModDelUserVar('modules', 'regen');
    if(xarModGetUserVar('modules', 'selstyle'))     xarModDelUserVar('modules', 'selstyle');
    if(xarModGetUserVar('modules', 'selfilter'))    xarModDelUserVar('modules', 'selfilter');
    if(xarModGetUserVar('modules', 'selsort'))      xarModDelUserVar('modules', 'selsort');
    if(xarModGetUserVar('modules', 'hidestats'))    xarModDelUserVar('modules', 'hidestats');
    if(xarModGetUserVar('modules', 'selmax'))       xarModDelUserVar('modules', 'selmax');
    if(xarModGetUserVar('modules', 'startpage'))    xarModDelUserVar('modules', 'startpage');
        
    // all done
    return true;
}

?>