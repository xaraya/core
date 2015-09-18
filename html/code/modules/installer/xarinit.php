<?php
/**
 * Install and Upgarde Xaraya
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/200.html
 */
/**
 * Install Xaraya
 *
 * @author Johnny Robeson
 * @return boolean
 */
function installer_init()
{
    // Initialisation successful
    return true;
}

/**
 * Upgrade this module from an old version
 *
 * @param oldVersion
 * @return boolean true on success, false on failure
 */
function installer_upgrade($oldversion)
{
    // Upgrade dependent on old version number
    switch ($oldversion) {
        case '2.0.0':
      break;
    }
    return true;
}

/**
 * Delete this module
 *
 * @return boolean
 */
function installer_delete()
{
    // this module cannot be removed
    return false;
}

?>
