<?php
// File: $Id$
// ----------------------------------------------------------------------
// PostNuke Content Management System
// Copyright (C) 2002 by the PostNuke Development Team.
// http://www.postnuke.com/
// ----------------------------------------------------------------------
// LICENSE
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License (GPL)
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// To read the license please visit http://www.gnu.org/copyleft/gpl.html
// ----------------------------------------------------------------------
// Original Author of this file: Paul Rosania
// Adapted from: index.php (Jim McDonald)
// Purpose of this file: Entry point for PostNuke installer
// ----------------------------------------------------------------------

// INSTALLER THEME
define('PNINSTALL_THEME','SeaBreeze');


// 1. select language
// ---set language
// 2. read license agreement
// ---check agreement state
// 3. set config.php permissions
// ---check permissions
// 4. input database information
// ---verify, write config.php, install basic dataset (inc. default admin), bootstrap
// 5. create administrator
// ---modify administrator information in nuke_users
// 6. pick optional components
// ---call optional components' init funcs, disable non-reusable areas of install module
// 7. finished!

// Include pnCore
include 'includes/pnCore.php';
// Include extra functions
include 'modules/installer/pnfunctions.php';

// Install Phases
define ('PNINSTALL_PHASE_WELCOME',             '1');
define ('PNINSTALL_PHASE_LANGUAGE_SELECT',     '2');
define ('PNINSTALL_PHASE_LICENSE_AGREEMENT',   '3');
/*TODO: rename to PNINSTALL_PHASE_SYSTEM_CHECK unless we want to implement another
phase for php settings check ..magic quotes, register globals, etc..
*/
define ('PNINSTALL_PHASE_PERMISSIONS_CHECK',   '4');
define ('PNINSTALL_PHASE_SETTINGS_COLLECTION', '5');
// FIXME: <marco> doesn't make more sense to call it DATABASE_CREATION?
define ('PNINSTALL_PHASE_ADMIN_CREATION',      '6');
define ('PNINSTALL_PHASE_PLUGIN_INSTALL',      '7');
define ('PNINSTALL_PHASE_FINISHED',            '8');

/**
 * Entry function for the installer
 *
 * @access private
 * @param phase the install phase to load
 * @returns bool
 * @return true on success, false on failure
 */
function pnInstallMain($phase = PNINSTALL_PHASE_WELCOME)
{
    pnCoreInit(PNCORE_SYSTEM_NONE); // Does not initialise any optional system

    // Handle installation phase designation
    $phase = (int) pnRequestGetVar('install_phase', 'POST');
    if ($phase == 0) {
        $phase = 1;
    }

    // Handle language setting
    /*
    if (empty($HTTP_POST_VARS['install_language']) || !is_string($HTTP_POST_VARS['install_language'])) {
        $language = 'eng';
    } else {
        $language = $HTTP_POST_VARS['install_language'];
    }
    */

    // Make sure we should still be here
    if ($phase >= PNINSTALL_PHASE_ADMIN_CREATION) {
        pnResponseRedirect('index.php?module=installer&type=admin&func=bootstrap');
    }

    // Load the installer module, the hard way - file check too
    $installer_admin_file = 'modules/installer/pnadminapi.php';
    require $installer_admin_file;

    // Run the function, check for existence
    $mod_func = 'installer_adminapi_phase'.$phase;

    if (function_exists($mod_func)) {
        $data = $mod_func();

        // Handle exceptions
        if (pnExceptionMajor() != PN_NO_EXCEPTION) {
            return;
        }

        $return = pnTplInstall('installer', 'admin', 'phase'.$phase, $data);
    } else {
        // exception time!
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'MODULE_FUNCTION_NOT_EXIST',
                       new SystemException(__FILE__."(".__LINE__."): Module API function $mod_func doesn't exist."));return;
    }

    // Render page
    // Load the template, the hard way - file check too
    $installer_tpl_file = 'themes/SeaBreeze/pages/install.pnt';
    extract ($data, EXTR_OVERWRITE);
    require $installer_tpl_file;

    // Handle exceptions
    if (pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }

    //echo $pageOutput;
    
    return true;
}

$res = pnInstallMain($phase);

if (!isset($res)) {

    // If we're here there must be surely an uncaught exception
    $text = pnML('Caught exception');
    $text .= '<br />';
    $text .= pnExceptionRender('html');

    pnLogException(PNLOG_LEVEL_ERROR);

    // TODO: <marco> Do fallback if raised exception is coming from template engine
    if (pnExceptionId() == 'TEMPLATE_NOT_EXIST') {
        echo '<html><head><title>Error</title><body>' . $text . '</body></html>';
    } else {
        // It's important here to free exception before caling pnTpl_renderPage
        pnExceptionFree();
        // Render page
        $pageOutput = pnTpl_renderPage($text);
        if (pnExceptionMajor() != PN_NO_EXCEPTION) {
            // Fallback to raw html
            $msg = '<font color="red">The current page is shown because the Blocklayout Template Engine failed to render the page, however this could be due to a problem not in BL itself but in the template. BL has raised or has left uncaught the following exception:</font>';
            $msg .= '<br /><br />';
            $msg .= pnExceptionRender('html');
            $msg .= '<br />';
            $msg .= '<font color="red">The following exception is instead the exception caught from the main catch clause (Please note that they could be the same if they were raised inside BL or inside the template):</font>';
            $msg .= '<br /><br />';
            $msg .= $text;
            echo "<html><head><title>Error</title><body>$msg</body></html>";
        } else {
            echo $pageOutput;
        }
    }
}

// Kill the debugger
pnCore_disposeDebugger();

?>
