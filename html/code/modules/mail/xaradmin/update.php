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
    if (!xarSecurity::check('EditMail')) return;
     
    // Need to pass object en itemid ourselves now as update has the 'object_' prefix apparently, doh!
    if(!xarVar::fetch('objectid',   'isset', $args['objectid'],    NULL, xarVar::DONT_SET)) {return;}
    if(!xarVar::fetch('itemid',     'isset', $args['itemid'],      NULL, xarVar::DONT_SET)) {return;}

    return xarMod::guiFunc('dynamicdata','admin','update',$args);
}
