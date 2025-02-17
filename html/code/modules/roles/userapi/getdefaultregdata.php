<?php

/**
 * @package modules\roles
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Roles\UserApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Roles\UserApi;
use xarMod;
use xarModVars;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles userapi getdefaultregdata function
 * @extends MethodClass<UserApi>
 */
class GetdefaultregdataMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * getdefaultregdata  - get the default registration module data
     * @param array<string,mixed> $args array of optional parameters<br/>
     * @return array defaultregmodname string, empty if no active registration module
     * defaultregmodactive boolean, regmodule is active or not
     * @author Jo Dalle Nogare <jojodee@xaraya.com>
     * @see UserApi::getdefaultregdata()
     */
    public function __invoke(array $args = [])
    {
        $defaultregdata      = [];
        $defaultregmodname   = '';
        $defaultregmodactive = false;
        $defaultregmodname    = xarModVars::get('roles', 'defaultregmodule');

        if (!empty($defaultregmodname)) {
            //check the module is available
            if (xarMod::isAvailable($defaultregmodname)) {
                //We can't really assume people will want this module as registration
                //Rethink - what we need to avert this problem
                if (xarModVars::get($defaultregmodname, 'allowregistration') == 1) {
                    $defaultregmodactive = true;
                } else {
                    $defaultregmodactive = false;
                }
            }
        } else {
            if (xarMod::isAvailable('registration')) {
                //for now - set the registration module but don't make it the active registration
                //the case where somehow the defautlregmodule modvar is unset or empty
                $defaultregmodname   = 'registration';
                $defaultregmodactive = false;
            }
        }
        //We can't assume any registration module is installed as it's optional, so go with what we have

        $defaultregdata = ['defaultregmodname'   => $defaultregmodname,
            'defaultregmodactive' => $defaultregmodactive];

        return $defaultregdata;
    }
}
