<?php
/**
 * @package modules
 * @subpackage mail module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/771.html
 */

function mail_admin_update($args = array())
{
    // Need to pass object en itemid ourselves now as update has the 'object_' prefix apparently, doh!
    if(!xarVarFetch('objectid',   'isset', $args['objectid'],    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemid',     'isset', $args['itemid'],      NULL, XARVAR_DONT_SET)) {return;}

    return xarMod::guiFunc('dynamicdata','admin','update',$args);
}
?>