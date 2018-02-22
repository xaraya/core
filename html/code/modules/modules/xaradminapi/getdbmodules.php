<?php
/**
 * @package modules\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1.html
 */

/**
 * Get all modules in the database
 *
 * @param array    $args array of optional parameters<br/>
 *        integer  $args['regid'] - optional regid to retrieve
 * @return array modules in the database
 */
function modules_adminapi_getdbmodules(Array $args=array())
{
    // Get arguments
    extract($args);

    // Check for $regId
    $modregid = 0;
    if (isset($regId)) {
        $modregid = $regId;
    }

    $dbconn = xarDB::getConn();
    $xartable =& xarDB::getTables();

    $dbModules = array();

    // Get all modules in DB
    $sql = "SELECT regid, name, directory, class, version, state
            FROM $xartable[modules] ";

    if ($modregid) {
        $sql .= " WHERE $xartable[modules].regid = ?";
        $bindvars = array($modregid);
    } else {
        $bindvars = array();
    }
    $stmt = $dbconn->prepareStatement($sql);
    $result = $stmt->executeQuery($bindvars);

    while($result->next()) {
        list($regid, $name, $directory, $class, $version, $state) = $result->fields;

        // If returning one module, then push array without name index
        if ($modregid) {
            $dbModules = array('name'    => $name,
                               'regid'   => $regid,
                               'version' => $version,
                               'class'   => $class,
                               'state'   => $state);
        } else {
            //Push it into array (should we change to index by regid instead?)
            $dbModules[$name] = array('name'    => $name,
                                      'regid'   => $regid,
                                      'version' => $version,
                                      'class'   => $class,
                                      'state'   => $state);
        }
    }
    $result->close();

    return $dbModules;
}

?>
