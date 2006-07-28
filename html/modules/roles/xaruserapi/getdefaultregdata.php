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
 * @return array  defaultregmodname string, empty if no active registration module
 *                defaultregmodactive boolean, regmodule is active or not
 *
 * @author Jo Dalle Nogare <jojodee@xaraya.com>
 */
function roles_userapi_getdefaultregdata()
{
    $defaultregdata=array();
    $defaultregmodname='';
    $defaultregmodactive=false;
    //get the default reg module if it exits
    $defaultregmodid =(int)xarModGetVar('roles','defaultregmodule');

    if (isset($defaultregmodid) && is_int($defaultregmodid) && ($defaultregmodid > 0)) {
        $defaultregmodname = xarModGetNameFromId($defaultregmodid);
        //check the module is available
        if (xarModIsAvailable($defaultregmodname)) {
           //We can't really assume people will want this module as registration,
           if (xarModGetVar($defaultregmodname, 'allowregistration')==1) {
              $defaultregmodactive=true;
           }
        }
    }
    //We can't assume any registration module is installed as it's optional, so go with what we have

    $defaultregdata=array('defaultregmodname'   => $defaultregmodname,
                          'defaultregmodactive' => $defaultregmodactive);

    return $defaultregdata;
}
?>