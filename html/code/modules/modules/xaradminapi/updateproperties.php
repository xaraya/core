<?php
/**
 * @package modules
 * @subpackage modules module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/1.html
 */
/**
 * Update module information
 *
 * @author Xaraya Development Team
 * @param array    $args array of optional parameters<br/>
 *        integer  $args['regid'] the id number of the module to update<br/>
 *        string   $args['displayname'] the new display name of the module<br/>
 *        string   $args['admincapable'] the whether the module shows an admin menu<br/>
 *        string   $args['usercapable'] the whether the module shows a user menu
 * @return boolean true on success, false on failure
 */
function modules_adminapi_updateproperties(Array $args=array())
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($regid)) throw new EmptyParameterException('regid');

    // Security Check
    if(!xarSecurityCheck('AdminModules',0,'All',"All:All:$regid")) return;

    // Update
    $xartable =& xarDB::getTables();
    $q = 'UPDATE ' . $xartable['modules'] . ' SET ';
    $uparts=array(); $bindvars=array();
    //    if (isset($displayname)) {$uparts[] = 'directory=?'; $bindvars[] = $displayname;}
    if (isset($admincapable)) {$uparts[] = 'admin_capable=?'; $bindvars[] = $admincapable;}
    if (isset($usercapable))  {$uparts[] = 'user_capable=?';  $bindvars[] = $usercapable; }
    if (isset($version))      {$uparts[] = 'version=?';       $bindvars[] = $version;}
    if (isset($class))        {$uparts[] = 'class=?';         $bindvars[] = $class;}
    if (isset($category))     {$uparts[] = 'category=?';      $bindvars[] = $category;}
    if(!empty($uparts)) {
        // We have something to update
        $q .= join(',',$uparts) . ' WHERE regid=?';
        $bindvars[] = $regid;
        $dbconn = xarDB::getConn();
        $dbconn->Execute($q, $bindvars);
    }
    return true;
}

?>
