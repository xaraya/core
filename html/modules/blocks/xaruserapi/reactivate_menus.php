<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
 */

/**
 * reset all menus to the active state
 * this is primarily used to prevent users still having
 * collapsed menus if the administrator turns off
 * collapseable menu support
 * @author Jim McDonald, Paul Rosania
 * @return true on success, false on failure
 */
function blocks_userapi_reactivate_menus()
{
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $ublockstable = $xartable['userblocks'];

    $query="UPDATE $ublockstable
               SET active=?
             WHERE active=?";

    $dbconn->Execute($query,array(1,0));

    return true;
}

?>
