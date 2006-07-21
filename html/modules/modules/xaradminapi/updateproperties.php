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
    $xartable =& xarDBGetTables();
    $q = 'UPDATE ' . $xartable['modules'] . ' SET ';
    $uparts=array(); $bindvars=array();
    //    if (isset($displayname)) {$uparts[] = 'xar_directory=?'; $bindvars[] = $displayname;}
    if (isset($admincapable)) {$uparts[] = 'xar_admin_capable=?'; $bindvars[] = $admincapable;}
    if (isset($usercapable))  {$uparts[] = 'xar_user_capable=?';  $bindvars[] = $usercapable; }
    if (isset($version))      {$uparts[] = 'xar_version=?';       $bindvars[] = $version;}
    if (isset($class))        {$uparts[] = 'xar_class=?';         $bindvars[] = $class;}
    if (isset($category))     {$uparts[] = 'xar_category=?';      $bindvars[] = $category;}
    if(!empty($uparts)) {
        // We have something to update
        $q .= join(',',$uparts) . ' WHERE xar_regid=?';
        $bindvars[] = $regid;
        $dbconn = xarDbGetConn();
        $dbconn->Execute($q, $bindvars);
    }
    return true;
}

?>
