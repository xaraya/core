<?php
/**
 * Categories Module
 *
 * @package modules
 * @subpackage categories module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/147.html
 *
 */

/**
 * udpate item from categories_admin_modify
 */
function categories_admin_updatecat()
{
    if (!xarVarFetch('creating', 'bool', $creating)) return;

    if ($creating) {
        return xarMod::guiFunc('categories','admin','create');
    } else {
        return xarMod::guiFunc('categories','admin','update');
    }
}
?>
