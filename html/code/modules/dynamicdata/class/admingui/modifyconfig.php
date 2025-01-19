<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\DataObject\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\DataObject\AdminGui;
use xarConfigVars;
use xarController;
use xarMod;
use xarModVars;
use xarSec;
use xarSecurity;
use xarTpl;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * dynamicdata admin modifyconfig function
 * @extends MethodClass<AdminGui>
 */
class ModifyconfigMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Modify the configuration settings of this module
     * Standard GUI function to display and update the configuration settings of the module based on input data.
     * @return mixed data array for the template display or output display string if invalid data submitted
     * @todo use context
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!xarSecurity::check('AdminDynamicData')) {
            return;
        }

        $data = ['tab' => 'general'];
        if (!$this->var()->fetch('phase', 'str:1:100', $phase, 'modify', xarVar::NOT_REQUIRED, xarVar::PREP_FOR_DISPLAY)) {
            return;
        }
        if (!$this->var()->fetch('tab', 'str:1', $data['tab'], 'general', xarVar::NOT_REQUIRED)) {
            return;
        }

        $data['module_settings'] = xarMod::apiFunc('base', 'admin', 'getmodulesettings', ['module' => 'dynamicdata']);
        $data['module_settings']->setFieldList('items_per_page, use_module_alias, module_alias_name, use_module_icons');
        $data['module_settings']->getItem();
        switch (strtolower($phase)) {
            case 'modify':
            default:

                break;

            case 'update':
                // Confirm authorisation code
                if (!$this->sec()->confirmAuthKey()) {
                    return $this->ctl()->badRequest('bad_author');
                }
                if (!$this->var()->fetch('debugmode', 'checkbox', $debugmode, xarModVars::get('dynamicdata', 'debugmode'), xarVar::NOT_REQUIRED)) {
                    return;
                }
                if (!$this->var()->fetch('show_queries', 'checkbox', $show_queries, xarConfigVars::get(null, 'Site.BL.ShowQueries'), xarVar::NOT_REQUIRED)) {
                    return;
                }
                if (!$this->var()->fetch('suppress_updates', 'checkbox', $suppress_updates, false, xarVar::NOT_REQUIRED)) {
                    return;
                }
                // if (!$this->var()->fetch('administrators', 'str', $administrators, '', xarVar::NOT_REQUIRED)) return;
                if (!$this->var()->fetch('caching', 'checkbox', $caching, xarModVars::get('dynamicdata', 'caching'), xarVar::NOT_REQUIRED)) {
                    return;
                }
                if (!$this->var()->fetch('twig_support', 'checkbox', $twig_support, false, xarVar::NOT_REQUIRED)) {
                    return;
                }

                $isvalid = $data['module_settings']->checkInput();
                if (!$isvalid) {
                    $data['context'] ??= $this->getContext();
                    return xarTpl::module('dynamicdata', 'admin', 'modifyconfig', $data);
                } else {
                    $itemid = $data['module_settings']->updateItem();
                }

                /*
                $admins = explode(',',$administrators);
                $validadmins = array();
                foreach ($admins as $admin) {
                    if (empty($admin)) continue;
                    $user = xarMod::apiFunc('roles','user','get',array('uname' => trim($admin)));
                    if(!empty($user)) $validadmins[$user['uname']] = $user['uname'];
                }
                xarModVars::set('dynamicdata', 'administrators', serialize($validadmins));
                */
                xarModVars::set('dynamicdata', 'debugmode', $debugmode);
                xarConfigVars::set(null, 'Site.BL.ShowQueries', $show_queries);
                xarModVars::set('dynamicdata', 'suppress_updates', $suppress_updates);
                xarModVars::set('dynamicdata', 'caching', $caching);
                xarModVars::set('dynamicdata', 'twig_support', $twig_support);
                // save to cache if enabled
                xarModVars::cache('dynamicdata');
                break;
        }
        return $data;
    }
}
