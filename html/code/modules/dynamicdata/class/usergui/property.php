<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\DataObject\UserGui;

use Xaraya\Modules\MethodClass;
use Xaraya\DataObject\UserGui;
use Exception;
use xarConfigVars;
use xarController;
use xarModVars;
use xarUser;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * dynamicdata user property function
 * @extends MethodClass<UserGui>
 */
class PropertyMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Execute a function in a standalone property
     * @package modules\dynamicdata
     * @subpackage dynamicdata
     * @category Xaraya Web Applications Framework
     * @version 2.4.0
     * @copyright see the html/credits.html file in this release
     * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
     * @link http://xaraya.info/index.php/release/182.html
     * @author Marc Lutolf <mfl@netspan.ch>
     * @param array<string,mixed> $args
     * @return string|bool|void
     */
    public function __invoke(array $args = [])
    {
        if (!$this->var()->fetch('prop', 'str', $property, '', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!$this->var()->fetch('act', 'str', $act, '', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (empty($property) || empty($act)) {
            $msg = $this->ml('Property not found');
            return $this->ctl()->notFound($msg);
        }

        try {
            sys::import('properties.' . $property . '.' . $act);
            $function = $property . "_" . $act;
            $function();
            return true;
        } catch (Exception $e) {
            if (xarModVars::get('dynamicdata', 'debugmode') && in_array(xarUser::getVar('id'), xarConfigVars::get(null, 'Site.User.DebugAdmins'))) {
                echo "<pre>";
                print($e->__toString());
            } else {
                $msg = $this->ml('Property not found');
                return $this->ctl()->notFound($msg);
            }
        }
    }
}
