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
use Exception;
use xarConfigVars;
use xarController;
use xarModVars;
use xarUser;
use xarVar;
use sys;

sys::import('modules.dynamicdata.method');


/**
 * dynamicdata user property function
 * @extends MethodClass<UserGui>
 */
class PropertyMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Execute a function in a standalone property
     * @param array<string,mixed> $args
     * @return string|bool|void
     * @see UserGui::property()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        $this->var()->check('prop', $property, 'str', '');
        $this->var()->check('act', $act, 'str', '');
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
            if ($this->mod()->getVar('debugmode') && xarUser::isDebugAdmin()) {
                echo "<pre>";
                print($e->__toString());
            } else {
                $msg = $this->ml('Property not found');
                return $this->ctl()->notFound($msg);
            }
        }
    }
}
