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