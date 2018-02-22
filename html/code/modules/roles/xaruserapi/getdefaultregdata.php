<?php
/**
 * Get the default registraton module and related data if it exists
 *
 * @package modules\roles
 * @subpackage roles
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/27.html
 */
/**
 * getdefaultregdata  - get the default registration module data
 *
 * @param array    $args array of optional parameters<br/>
 * @return array  defaultregmodname string, empty if no active registration module
 *                defaultregmodactive boolean, regmodule is active or not
 *
 * @author Jo Dalle Nogare <jojodee@xaraya.com>
 */
function roles_userapi_getdefaultregdata()
{
    $defaultregdata      = array();
    $defaultregmodname   = '';
    $defaultregmodactive = false;
    $defaultregmodname    = xarModVars::get('roles','defaultregmodule');

    if (!empty($defaultregmodname)) {
        //check the module is available
        if (xarModIsAvailable($defaultregmodname)) {
           //We can't really assume people will want this module as registration
           //Rethink - what we need to avert this problem
           if (xarModVars::get($defaultregmodname, 'allowregistration')==1) {
              $defaultregmodactive=true;
           } else {
              $defaultregmodactive=false;
           }
        }
    } else {
         if (xarModIsAvailable('registration')) {
           //for now - set the registration module but don't make it the active registration
           //the case where somehow the defautlregmodule modvar is unset or empty
           $defaultregmodname   = xarModGetNameFromId('registration');
           $defaultregmodactive = false;
         }
    }
    //We can't assume any registration module is installed as it's optional, so go with what we have

    $defaultregdata=array('defaultregmodname'   => $defaultregmodname,
                          'defaultregmodactive' => $defaultregmodactive);

    return $defaultregdata;
}
?>