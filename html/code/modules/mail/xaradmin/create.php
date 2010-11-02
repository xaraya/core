<?php
/**
 * @package modules
 * @subpackage mail module
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @link http://xaraya.com/index.php/release/771.html
 */

function mail_admin_create($args)
{
    // User requested to create a new mailqueue
    // We have to do 2 things:
    // 1. Create a queue for storage if needed
    // 2. Create a 'record' in the Queue definition object
    // If the first fails for some reason, we do not do the second and return to the edit screen if possible
    return xarMod::guiFunc('dynamicdata','admin','create',$args);
}
?>