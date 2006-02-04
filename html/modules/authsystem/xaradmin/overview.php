<?php
/**
 * Overview displays standard Overview page
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Authsystem module
 * @link http://xaraya.com/index.php/release/42.html
 */

/**
 * Overview displays standard Overview page
 * Used to call the template that provides display of the overview
 *
 * @author Jo Dalle Nogare <jojodee@xaraya.com>
 * @returns array xarTplModule with $data containing template data
 * @return array containing the menulinks for the overview item on the main manu
 * @since 29 Jan 2006
 */
function authsystem_admin_overview()
{
   /* Security Check */
    if (!xarSecurityCheck('AdminAuthsystem',0)) return;

    $data=array();

    /* if there is a separate overview function return data to it
     * else just call the main function that usually displays the overview
     */

    return xarTplModule('authsystem', 'admin', 'main', $data,'main');
}

?>