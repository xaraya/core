<?php
/**
 * Install and Upgarde Xaraya
 * @package Installer
 * @copyright (C) 2002-2009 The Digital Development Foundation
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
 * Upgrade this module from an old version
 *
 * @param oldVersion
 * @returns bool
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
 * @return bool
 */
function installer_delete()
{
    // this module cannot be removed
    return false;
}

?>
