<?php
/**
 * Modify the system configuration File
 * @package Installer
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Installer
 * @link http://xaraya.com/index.php/release/200.html
 */

/**
 * Modify the system configuration file
 *
 * @author Johnny Robeson
 * @param string args['dbHost']
 * @param string args['dbName']
 * @param string args['dbUname']
 * @param string args['dbPass']
 * @param string args['prefix']
 * @param string args['dbType']
 * @return bool
 */
function installer_adminapi_modifyconfig($args)
{
    extract($args);

    // fixes instances where passwords contains --> '
    $dbPass = addslashes($dbPass);

    $systemConfigFile = sys::varpath() . '/config.system.php';
    $config_php = join('', file($systemConfigFile));

    //$dbUname = base64_encode($dbUname);
    //$dbPass = base64_encode($dbPass);

    $config_php = preg_replace('/\[\'DB.Type\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.Type'] = '$dbType';", $config_php);
    $config_php = preg_replace('/\[\'DB.Host\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.Host'] = '$dbHost';", $config_php);
    $config_php = preg_replace('/\[\'DB.UserName\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.UserName'] = '$dbUname';", $config_php);
    $config_php = preg_replace('/\[\'DB.Password\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.Password'] = '$dbPass';", $config_php);
    $config_php = preg_replace('/\[\'DB.Name\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.Name'] = '$dbName';", $config_php);
    $config_php = preg_replace('/\[\'DB.TablePrefix\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.TablePrefix'] = '$dbPrefix';", $config_php);
    //$config_php = preg_replace('/\[\'DB.Encoded\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.Encoded'] = '1';", $config_php);

    $fp = fopen ($systemConfigFile, 'wb');
    fwrite ($fp, $config_php);
    fclose ($fp);

    return true;
}

/**
 * Include a module init file and run a function
 *
 * @access public
 * @param args['directory'] the directory to include
 * @param args['initfunc'] init|upgrade|remove
 * @returns bool
 * @throws BAD_PARAM, MODULE_FILE_NOT_EXIST, MODULE_FUNCTION_NOT_EXIST
 */
function installer_adminapi_initialise($args)
{
    extract($args);


    if (empty($directory) || empty($initfunc)) {
        throw new EmptyParameterException('directory or initfunc');
    }

    $osDirectory = xarVarPrepForOS($directory);
    $modInitFile = 'modules/'. $osDirectory. '/xarinit.php';


    if(!file_exists($modInitFile)) throw new FileNotFoundException($modInitFile);
    sys::import('modules.'.$osDirectory.'.xarinit');

    // Run the function, check for existence

    $initFunc = $osDirectory.'_'.$initfunc;
    if (function_exists($initFunc)) {
        $res = $initFunc();

        if ($res == false) {
            // exception
            throw new Exception('Core initialization failed!');
        }
    } else {
        // modulename_init() not found?!
        throw new FunctionNotFoundException($initFunc);
    }

    return true;
}

/**
 * Create a database
 *
 * @access public
 * @param args['dbName']
 * @param args['dbType']
 * @returns bool
 * @throws BAD_PARAM, DATABASE_ERROR
 */
function installer_adminapi_createdb($args)
{
    extract($args);
    // Load in Table Maintainance API
    sys::import('xarTableDDL');

    // Start connection, but use the configured connection db
   $createArgs = array(
                       'userName' => $dbUname,
                       'password' => $dbPass,
                       'databaseHost' => $dbHost,
                       'databaseType' => $dbType,
                       'databaseName' => $dbName, 
                       'systemTablePrefix' => $dbPrefix,
                       'siteTablePrefix' => $dbPrefix);
   $dbconn =& xarDBNewConn($createArgs);
   
   $query = xarDBCreateDatabase($dbName,$dbType);
   $result =& $dbconn->Execute($query);
   return true;
}


/**
 * CheckForField
 *
 * @access public
 * @param args['field_name']
 * @param args['table_name']
 * @returns true if field exists false otherwise
 * @author Sean Finkle, John Cox
 */
function installer_adminapi_CheckForField($args)
{
    extract($args);

    // Argument check - make sure that all required arguments are present,
    // if not then set an appropriate error message and return
    if ((!isset($field_name)) || (!isset($table_name))) {
        throw new EmptyParameterException('field_name or table_name');
    }

    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    // CHECKME: Is this portable? In any case, use the meta classes
    $query = "desc $table_name";
    $result =& $dbconn->executeQuery($query);

    
    while($result->next()) {
        if ($result[Field] == $field_name) {
            return true;
        }
    }

    return false;
}

/**
 * GetFieldType
 *
 * @access public
 * @param args['field_name']
 * @param args['table_name']
 * @returns field type
 * @author Sean Finkle, John Cox
 */
function installer_adminapi_GetFieldType($args)
{
    extract($args);

    // Argument check - make sure that all required arguments are present,
    // if not then set an appropriate error message and return
    if ((!isset($field_name)) || (!isset($table_name))) {
        throw new EmptyParameterException('field_name or table_name');
    }

    $dbconn =& xarDBGetConn();

    // CHECKME: Is this portable? In any case, use the meta classes
    $query = "desc $table_name";
    $result = $dbconn->executeQuery($query);

    while($result->next()) {
        if ($result[Field] == $field_name) {
            return ($row[Type]);
        }
    }
    return;
}

/**
 * CheckTableExists
 *
 * @access public
 * @param args['table_name']
 * @returns true if field exists false otherwise
 * @author Sean Finkle, John Cox
 */
function installer_adminapi_CheckTableExists($args)
{
    extract($args);

    // Argument check - make sure that all required arguments are present,
    // if not then set an appropriate error message and return
    if (!isset($table_name)) throw new EmptyParameterException('table_name');

    $dbconn =& xarDBGetConn();
    $dbInfo = $dbconn->getDatabaseInfo();
    return $dbInfo->hasTable($table_name);
}

?>
