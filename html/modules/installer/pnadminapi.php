<?php // $Id$
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
// Original Author of file: Paul Rosania
// Purpose of file: Installer display functions
// ----------------------------------------------------------------------



// Update database information in config.php
// TODO: EXCEPTIONS!!
function installer_adminapi_modifyconfig($dbhost, $dbuname, $dbpass, $dbname, $prefix, $dbtype)
{
    $systemConfigFile = pnCoreGetVarDirPath() . '/config.system.php';
    $config_php = join('', file($systemConfigFile));
    if (isset($HTTP_ENV_VARS['OS']) && strstr($HTTP_ENV_VARS['OS'], 'Win')) {
        $system = 1;
    } else {
        $system = 0;
    }

    //$dbuname = base64_encode($dbuname);
    //$dbpass = base64_encode($dbpass);

    $config_php = preg_replace('/\[\'DB.Type\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.Type'] = '$dbtype';", $config_php);
    $config_php = preg_replace('/\[\'DB.Host\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.Host'] = '$dbhost';", $config_php);
    $config_php = preg_replace('/\[\'DB.UserName\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.UserName'] = '$dbuname';", $config_php);
    $config_php = preg_replace('/\[\'DB.Password\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.Password'] = '$dbpass';", $config_php);
    $config_php = preg_replace('/\[\'DB.Name\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.Name'] = '$dbname';", $config_php);
    $config_php = preg_replace('/\[\'DB.TablePrefix\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.TablePrefix'] = '$prefix';", $config_php);
   // $config_php = preg_replace('/\[\'system\'\]\s*=\s*(\'|\")(.*)\\1;/', "['system'] = '$system';", $config_php);
   // $config_php = preg_replace('/\[\'encoded\'\]\s*=\s*(\'|\")(.*)\\1;/', "['encoded'] = '1';", $config_php);
    
    $fp = fopen ($systemConfigFile, 'w+');
    fwrite ($fp, $config_php);
    fclose ($fp);
}

?>
