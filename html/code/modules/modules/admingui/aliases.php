<?php

/**
 * @package modules\modules
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Modules\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Modules\AdminGui;
use xarConfigVars;
use xarModAlias;
use xarSecurity;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * modules admin aliases function
 * @extends MethodClass<AdminGui>
 */
class AliasesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     *
     * @return array|void data for the template display
     * @see AdminGui::aliases()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!xarSecurity::check('AdminModules')) {
            return;
        }

        xarVar::fetch('name', 'str', $modname, null, xarVar::NOT_REQUIRED);
        xarVar::fetch('remove', 'str', $removealias, null, xarVar::NOT_REQUIRED);
        xarVar::fetch('add', 'str', $addalias, null, xarVar::NOT_REQUIRED);
        if (!empty($removealias) && !empty($modname)) {
            xarModAlias::delete($removealias, $modname);
        } elseif (!empty($addalias) && !empty($modname)) {
            xarModAlias::set($addalias, $modname);
        }
        $data['modname'] = $modname;
        $data['aliasesMap'] = xarConfigVars::get(null, 'System.ModuleAliases');
        ksort($data['aliasesMap']);
        return $data;
    }
}
