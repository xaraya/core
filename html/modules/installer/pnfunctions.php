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
// Original Author of this file: John Robeson
// Purpose of this file: extra functions for the installer
// ----------------------------------------------------------------------

function pnInstallGetTheme()
{
    return 'themes/'. PNINSTALL_THEME;
}

function pnInstallFunc($modName, $modType = 'user', $funcName = 'main', $args = array())
{
    if (empty($modName)) {
        $msg = pnML('Empty modname.');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    // Build function name and call function
    $modFunc = "{$modName}_{$modType}_{$funcName}";
    if (!function_exists($modFunc)) {
        $msg = pnML('Module function #(1) doesn\'t exist.', $modFunc);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'MODULE_FUNCTION_NOT_EXIST',
                       new SystemException($msg));
        return;
    }

    $tplData = $modFunc($args);

    if (!is_array($tplData)) {
        return $tplData;
    }

    $templateName = NULL;
    if (isset($tplData['_bl_template'])) {
        $templateName = $tplData['_bl_template'];
    }

    return pnTplInstall($modName, $modType, $funcName, $tplData, $templateName);
}

function pnInstallLoad($modName, $modType = 'user')
{
    static $loadedModuleCache = array();

   // pnLogMessage("pnModLoad: loading $modName:$modType");

    if (empty($modName)) {
        $msg = pnML('Empty modname.');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    if (isset($loadedModuleCache["$modName$modType"])) {
        // Already loaded from somewhere else
        return true;
    }

    // Load the module files
    $modOsType = pnVarPrepForOS($modType);
    $modOsDir = 'installer';

    $osfile = "modules/$modOsDir/pn$modOsType.php";
    if (!file_exists($osfile)) {
        // File does not exist
        $msg = pnML('Module file #(1) doesn\'t exist.', $osfile);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST',
                       new SystemException($msg));
        return;
    }

    // Load file
    include $osfile;
    $loadedModuleCache["$modName$modType"] = true;

    // Load the module translations files
   /* $res = pnMLS_loadModuleTranslations($modName, $modOsDir, $modType);
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return; // throw back exception
    }*/

    // Load database info
    //pnMod__loadDbInfo($modName, $modOsDir);

    // Module loaded successfully, notify the proper event
   // pnEvt_notify($modName, $modType, 'ModLoad', NULL);

    return true;
}
/**
 * Turn installer output into a template
 *
 * NOTE: this function is identical to pnTplModule without pnMod_getBaseInfo
 * prolly will get removed in the future, i have it just for consistancy
 *
 * @access public
 * @param modName the module name
 * @param modType user|admin
 * @param funcName module function to template
 * @param tplData arguments for the template
 * @param templateName the specific template to call
 * @returns string
 * @return output of the template
 **/
function pnTplInstall($modName, $modType, $funcName, $tplData = array(), $templateName = NULL)
{
    global $pnTpl_themeDir;

    if (!empty($templateName)) {
        $templateName = pnVarPrepForOS($templateName);
    }

    $modOsDir = 'installer';

    // Try theme template
    $sourceFileName = "$pnTpl_themeDir/modules/$modOsDir/$modType-$funcName" . (empty($templateName) ? '.pnt' : "-$templateName.pnt");
    if (!file_exists($sourceFileName)) {
        // Use internal template
        $sourceFileName = "modules/$modOsDir/pntemplates/$modType-$funcName" . (empty($templateName) ? '.pnd' : "-$templateName.pnd");
    }

    return pnTpl__executeFromFile($sourceFileName, $tplData);
}
?>
