<?php
/**
 * Modify the system configuration File
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/200.html
 */

/**
 * Modify the system configuration file
 *
 * @author Johnny Robeson
 * @param array    $args array of optional parameters<br/>
 *        string   $args['dbHost']<br/>
 *        string   $args['dbName']<br/>
 *        string   $args['dbUname']<br/>
 *        string   $args['dbPass']<br/>
 *        string   $args['prefix']<br/>
 *        string   $args['dbType']
 * @return boolean
 */
function installer_adminapi_modifyconfig(Array $args=array())
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
    $config_php = preg_replace('/\[\'DB.Charset\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.Charset'] = '$dbCharset';", $config_php);
    //$config_php = preg_replace('/\[\'DB.Encoded\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.Encoded'] = '1';", $config_php);

    $fp = fopen ($systemConfigFile, 'wb');
    fwrite ($fp, $config_php);
    fclose ($fp);

    return true;
}

/**
 * Modify one or more variables in a configuration file
 *
 * @author Marc Lutolf
 * @param array    $args array of optional parameters<br/>
 * @param string args['variables'] = array($name => $value,...)
 * @return boolean
 */

function installer_adminapi_modifysystemvars(Array $args=array())
{
    if (!isset($args['variables'])) throw new BadParameterException('variables');
    $configfile = sys::varpath() . '/config.system.php';
    if (isset($args['filepath'])) $configfile = $args['filepath'];
    try {
        $config_php = join('', file($configfile));
        foreach ($args['variables'] as $name => $value) {
            $config_php = preg_replace('/\[\''.$name.'\'\]\s*=\s*(\'|\")(.*)\\1;/', "['".$name."'] = '$value';", $config_php);
        }

        $fp = fopen ($configfile, 'wb');
        fwrite ($fp, $config_php);
        fclose ($fp);
        return true;

    } catch (Exception $e) {
        throw new FileNotFoundException($configfile);
    }
}

/**
 * Include a module init file and run a function
 *
 * @access public
 * @param array    $args array of optional parameters<br/>
 *        string   $args['directory'] the directory to include<br/>
 *        string   $args['initfunc'] init|upgrade|remove
 * @return boolean true on success, false on failure
 * @throws BAD_PARAM, MODULE_FILE_NOT_EXIST, MODULE_FUNCTION_NOT_EXIST
 */
function installer_adminapi_initialise(Array $args=array())
{
    extract($args);


    if (empty($directory) || empty($initfunc)) {
        throw new EmptyParameterException('directory or initfunc');
    }

    $osDirectory = xarVarPrepForOS($directory);
    $modInitFile = sys::code() . 'modules/'. $osDirectory. '/xarinit.php';


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
 * @param array    $args array of optional parameters<br/>
 *        string   $args['dbName']<br/>
 *        string   $args['dbType']
 * @return boolean true on success, false on failure
 * @throws BAD_PARAM, DATABASE_ERROR
 */
function installer_adminapi_createdb(Array $args=array())
{
    extract($args);
    // Load in Table Maintainance API
    sys::import('xaraya.xarTableDDL');

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

   $dbCharset = xarSystemVars::get(sys::CONFIG, 'DB.Charset');
   $query = xarDBCreateDatabase($dbName,$dbType,$dbCharset);
   $result =& $dbconn->Execute($query);
   return true;
}


/**
 * CheckForField
 *
 * @access public
 * @param array    $args array of optional parameters<br/>
 *        string   $args['field_name']<br/>
 *        string   $args['table_name']
 * @return boolean true if field exists false otherwise
 * @author Sean Finkle
 * @author John Cox
 */
function installer_adminapi_CheckForField(Array $args=array())
{
    extract($args);

    // Argument check - make sure that all required arguments are present,
    // if not then set an appropriate error message and return
    if ((!isset($field_name)) || (!isset($table_name))) {
        throw new EmptyParameterException('field_name or table_name');
    }

    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();

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
 * @param array    $args array of optional parameters<br/>
 *        string   $args['field_name']<br/>
 *        string   $args['table_name']
 * @return integer field type
 * @author Sean Finkle
 * @author John Cox
 */
function installer_adminapi_GetFieldType(Array $args=array())
{
    extract($args);

    // Argument check - make sure that all required arguments are present,
    // if not then set an appropriate error message and return
    if ((!isset($field_name)) || (!isset($table_name))) {
        throw new EmptyParameterException('field_name or table_name');
    }

    $dbconn = xarDB::getConn();

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
 * @param array    $args array of optional parameters<br/>
 *        string   $args['table_name']
 * @return boolean true if field exists false otherwise
 * @author Sean Finkle
 * @author John Cox
 */
function installer_adminapi_CheckTableExists(Array $args=array())
{
    extract($args);

    // Argument check - make sure that all required arguments are present,
    // if not then set an appropriate error message and return
    if (!isset($table_name)) throw new EmptyParameterException('table_name');

    $dbconn = xarDB::getConn();
    $dbInfo = $dbconn->getDatabaseInfo();
    return $dbInfo->hasTable($table_name);
}

?>
