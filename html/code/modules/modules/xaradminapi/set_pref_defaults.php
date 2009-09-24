<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
 */
/**
 * reset admin preferences to default module preferences
 *
 * @author Xaraya Development Team
 * @access public
 * @param none
 * @returns bool
 * @return true on success, false on failure
 * @throws BAD_PARAM
 */
function modules_adminapi_set_pref_defaults()
{
    // no beating around the bush here
    if(xarModUserVars::get('modules', 'hidecore'))     xarModDelUserVar('modules', 'hidecore');
    if(xarModUserVars::get('modules', 'regen'))        xarModDelUserVar('modules', 'regen');
    if(xarModUserVars::get('modules', 'selstyle'))     xarModDelUserVar('modules', 'selstyle');
    if(xarModUserVars::get('modules', 'selfilter'))    xarModDelUserVar('modules', 'selfilter');
    if(xarModUserVars::get('modules', 'selsort'))      xarModDelUserVar('modules', 'selsort');
    if(xarModUserVars::get('modules', 'hidestats'))    xarModDelUserVar('modules', 'hidestats');
    if(xarModUserVars::get('modules', 'selmax'))       xarModDelUserVar('modules', 'selmax');
    if(xarModUserVars::get('modules', 'startpage'))    xarModDelUserVar('modules', 'startpage');
        
    // all done
    return true;
}

?>
