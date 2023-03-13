<?php
/**
 * @package modules\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1.html
 */
/**
 * reset admin preferences to default module preferences
 *
 * @author Xaraya Development Team
 * @access public
 * @return boolean|void true on success, false on failure
 */
function modules_adminapi_set_pref_defaults()
{
    // no beating around the bush here
    if(xarModUserVars::get('modules', 'hidecore'))     xarModUserVars::delete('modules', 'hidecore');
    if(xarModUserVars::get('modules', 'regen'))        xarModUserVars::delete('modules', 'regen');
    if(xarModUserVars::get('modules', 'selstyle'))     xarModUserVars::delete('modules', 'selstyle');
    if(xarModUserVars::get('modules', 'selfilter'))    xarModUserVars::delete('modules', 'selfilter');
    if(xarModUserVars::get('modules', 'selsort'))      xarModUserVars::delete('modules', 'selsort');
    if(xarModUserVars::get('modules', 'hidestats'))    xarModUserVars::delete('modules', 'hidestats');
    if(xarModUserVars::get('modules', 'selmax'))       xarModUserVars::delete('modules', 'selmax');
    if(xarModUserVars::get('modules', 'startpage'))    xarModUserVars::delete('modules', 'startpage');
        
    // all done
    return true;
}
