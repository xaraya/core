<?php
/**
 * @package modules\mail
 * @subpackage mail
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/771.html
 */

function mail_admin_update($args = array())
{
     // Security
    if (!xarSecurityCheck('EditMail')) return;
     
    // Need to pass object en itemid ourselves now as update has the 'object_' prefix apparently, doh!
    if(!xarVarFetch('objectid',   'isset', $args['objectid'],    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemid',     'isset', $args['itemid'],      NULL, XARVAR_DONT_SET)) {return;}

    return xarMod::guiFunc('dynamicdata','admin','update',$args);
}
?>
