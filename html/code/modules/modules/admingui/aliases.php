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
use xarModAlias;
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
        if (!$this->sec()->checkAccess('AdminModules')) {
            return;
        }

        $this->var()->find('name', $modname, 'str', null);
        $this->var()->find('remove', $removealias, 'str', null);
        $this->var()->find('add', $addalias, 'str', null);
        if (!empty($removealias) && !empty($modname)) {
            xarModAlias::delete($removealias, $modname);
        } elseif (!empty($addalias) && !empty($modname)) {
            xarModAlias::set($addalias, $modname);
        }
        $data['modname'] = $modname;
        $data['aliasesMap'] = $this->config()->getVar('System.ModuleAliases');
        ksort($data['aliasesMap']);
        return $data;
    }
}
