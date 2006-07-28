<?php
/**
 * Get the default registraton module and related data if it exists
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * getdefaultregdata  - get the default registration module data
 *
 * @return array  defaultregmodulename string, empty if no active registration module
 *                defaultregmodactive boolean, regmodule is active or not
 *
 * @author Jo Dalle Nogare <jojodee@xaraya.com>
 */
function roles_userapi_getdefaultregdata()
{
    $defaultregdata=array();
    $defaultregmodulename='';
    $defaultregmodactive=false;
    //get the default reg module if it exits
    $defaultregmoduleid =xarModGetVar('roles','defaultregmodule');
    if (isset($defaultregmoduleid) && is_int($defaultregmoduleid) && ($defaultregmoduleid > 0)) {
        $defaultregmodulename =xarModGetNameFromId($defaultregmoduleid);
        //check the module is available
        if (!xarModIsAvailable($defaultregmodulename) && xarModIsAvailable('registration')) {
           //We can't really assume people will want this module as registration,
           if (xarModGetVar($defaultregmodulename, 'allowregistration')==1) {
              $defaultregmodactive=1;
           }
        }
    }
    //We can't assume any registration module is installed as it's optional, so go with what we have

    $defaultregdata=array('defaultregmodulename' =>$defaultregmodulename,
                         'defaultregmodactive'  => $defaultregmodactive);

    return $defaultregdata;
}

?>