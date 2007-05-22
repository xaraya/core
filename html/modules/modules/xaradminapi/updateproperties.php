<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
 */
/**
 * Update module information
 *
 * @author Xaraya Development Team
 * @param $args['regid'] the id number of the module to update
 * @param $args['displayname'] the new display name of the module
 * @param admincapable the whether the module shows an admin menu
 * @param usercapable the whether the module shows a user menu
 * @returns bool
 * @return true on success, false on failure
 */
function modules_adminapi_updateproperties($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($regid)) throw new EmptyParameterException('regid');

    // Security Check
    if(!xarSecurityCheck('AdminModules',0,'All',"All:All:$regid")) return;

    // Update
    $xartable = xarDB::getTables();
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
