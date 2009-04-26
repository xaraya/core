<?php
/**
 * Install and Upgarde Xaraya
 * @package Installer
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Installer
 * @link http://xaraya.com/index.php/release/200.html
 */
/**
 * Install Xaraya
 *
 * @author Johnny Robeson
 * @return bool
 */
function installer_init()
{
    // Initialisation successful
    return true;
}

/**
 * Upgrade Xaraya
 *
 * @param string $oldVersion the version we're upgrading from.
 * @return bool
 */
function installer_upgrade($oldVersion)
{
    switch($oldVersion) {
    case '1.0':
        // compatability upgrade, nothing to be done
        break;
    }
    return true;
}

/**
 * Delete Installer module
 *
 * @return bool
 */
function installer_delete()
{
    // this module cannot be removed
    return false;
}

?>
