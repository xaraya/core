<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\DynamicData\UserGui;

use Xaraya\Modules\DynamicData\MethodClass;
use Xaraya\Modules\DynamicData\UserGui;
use xarController;
use xarMod;
use xarModVars;
use xarSec;
use xarSecurity;
use xarServer;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * dynamicdata user main function
 * @extends MethodClass<UserGui>
 */
class MainMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * The main user interface function of this module.
     * This function is the default function, and is called whenever the module is
     * initiated without defining arguments.
     * The function displays a list of DD's available modules.
     * @param array<string,mixed> $args
     * @return array|bool empty array of data for the template display
     * @see UserGui::main()
     */
    public function __invoke(array $args = [])
    {
        $redirect = $this->mod()->getVar('frontend_page');
        if (!empty($redirect)) {
            $truecurrenturl = $this->ctl()->getCurrentURL([], false);
            $urldata = $this->mod()->apiFunc(
                'roles',
                'user',
                'parseuserhome',
                ['url' => $redirect,
                    'truecurrenturl' => $truecurrenturl]
            );
            $this->ctl()->redirect($urldata['redirecturl']);
            return true;
        }

        // get the list of main objects
        $startserial = $this->mod()->getVar('starter_object_list');
        if (!empty($startserial)) {
            $startlist = unserialize($startserial);
        } else {
            $startlist = [];
        }

        // define the list of main objects
        $this->var()->find('update', $update);
        if ((empty($startlist) || !empty($update)) &&
            $this->sec()->checkAccess('AdminDynamicData', 0)) {
            $this->var()->find('starter', $starter, 'array', []);
            if (is_array($starter) && $this->sec()->confirmAuthKey()) {
                $startlist = array_keys($starter);
                $this->mod()->setVar('starter_object_list', serialize($startlist));
                $this->ctl()->redirect($this->ctl()->getCurrentURL(['update' => null]));
                return true;
            }
        }

        $data = [
            'startlist' => $startlist,
            'update' => $update,
            'context' => $this->getContext(),
        ];
        return $data;
    }
}
