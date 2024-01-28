<?php
/**
 * Modify the system configuration File
 * @package modules\installer\installer
 * @subpackage installer
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/200.html
 */

/**
 * Modify one or more variables in a configuration file
 *
 * @author Marc Lutolf
 * @param array<string, mixed> $args array of optional parameters<br/>
 * @param string args['variables'] = array($name => $value,...)
 * @return boolean
 */

function installer_adminapi_modifysystemvars(Array $args=array())
{
    // We need variables to save
    if (!isset($args['variables'])) throw new BadParameterException('variables');
    
    // Get the path to the file we are updating
    if (!isset($args['scope'])) $args['scope'] = 'System';
    if ($args['scope'] == 'System') $configfile = sys::varpath() . '/config.system.php';
    elseif ($args['scope'] == 'Log') $configfile = sys::varpath() . '/logs/config.log.php';
    else throw new Exception(xarML("xarSystemVars: Unknown scope: '#(1)'.", $args['scope']));
    
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
 * @param array<string, mixed> $args array of optional parameters<br/>
 *        string   $args['directory'] the directory to include<br/>
 *        string   $args['initfunc'] init|upgrade|remove
 * @return boolean true on success, false on failure
 * @throws EmptyParameterException
 * @throws FileNotFoundException
 */
function installer_adminapi_initialise(Array $args=array())
{
    extract($args);


    if (empty($directory) || empty($initfunc)) {
        throw new EmptyParameterException('directory or initfunc');
    }

    $osDirectory = xarVar::prepForOS($directory);
    $modInitFile = sys::code() . 'modules/'. $osDirectory. '/xarinit.php';


    if(!file_exists($modInitFile)) throw new FileNotFoundException($modInitFile);
    sys::import('modules.'.$osDirectory.'.xarinit');

    // Run the function, check for existence

    $modInitFunc = $osDirectory.'_'.$initfunc;
    if (function_exists($modInitFunc)) {
        $res = $modInitFunc();

        if ($res == false) {
            // exception
            throw new Exception('Core initialization failed for ' . $modInitFunc);
        }
    } else {
        // modulename_init() not found?!
        throw new FunctionNotFoundException($modInitFunc);
    }

    return true;
}

/**
 * Create a database
 *
 * @access public
 * @param array<string, mixed> $args array of optional parameters<br/>
 *        string   $args['dbName']<br/>
 *        string   $args['dbType']
 * @return boolean true on success, false on failure
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
   $dbconn = xarDB::newConn($createArgs);

   $dbCharset = xarSystemVars::get(sys::CONFIG, 'DB.Charset');
   $query = xarTableDDL::createDatabase($dbName,$dbType,$dbCharset);
   $result = $dbconn->Execute($query);
   return true;
}


/**
 * CheckForField
 *
 * @access public
 * @param array<string, mixed> $args array of optional parameters<br/>
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
    $xartable =& xarDB::getTables();

    // CHECKME: Is this portable? In any case, use the meta classes
    $query = "desc $table_name";
    $result = $dbconn->ExecuteQuery($query);


    while($result->next()) {
        if ($result['Field'] == $field_name) {
            return true;
        }
    }

    return false;
}

/**
 * GetFieldType
 *
 * @access public
 * @param array<string, mixed> $args array of optional parameters<br/>
 *        string   $args['field_name']<br/>
 *        string   $args['table_name']
 * @return integer|void field type
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
        if ($result['Field'] == $field_name) {
            return ($row['Type']);
        }
    }
    return;
}

/**
 * CheckTableExists
 *
 * @access public
 * @param array<string, mixed> $args array of optional parameters<br/>
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
