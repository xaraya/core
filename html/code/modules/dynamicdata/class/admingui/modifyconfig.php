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

use Xaraya\DataObject\MethodClass;
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
     * @see AdminGui::modifyconfig()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!$this->sec()->checkAccess('AdminDynamicData')) {
            return;
        }

        $data = ['tab' => 'general'];
        if (!$this->var()->find('phase', $phase, 'str:1:100', 'modify')) {
            return;
        }
        if (!$this->var()->find('tab', $data['tab'], 'str:1', 'general')) {
            return;
        }

        $data['module_settings'] = $this->mod()->apiFunc('base', 'admin', 'getmodulesettings', ['module' => 'dynamicdata']);
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
                if (!$this->var()->find('debugmode', $debugmode, 'checkbox', $this->mod()->getVar('debugmode'))) {
                    return;
                }
                if (!$this->var()->find('show_queries', $show_queries, 'checkbox', xarConfigVars::get(null, 'Site.BL.ShowQueries'))) {
                    return;
                }
                if (!$this->var()->find('suppress_updates', $suppress_updates, 'checkbox', false)) {
                    return;
                }
                // if (!$this->var()->find('administrators', $administrators, 'str', '')) return;
                if (!$this->var()->find('caching', $caching, 'checkbox', $this->mod()->getVar('caching'))) {
                    return;
                }
                if (!$this->var()->find('twig_support', $twig_support, 'checkbox', false)) {
                    return;
                }

                $isvalid = $data['module_settings']->checkInput();
                if (!$isvalid) {
                    return $this->tpl()->module('dynamicdata', 'admin', 'modifyconfig', $data);
                } else {
                    $itemid = $data['module_settings']->updateItem();
                }

                /*
                $admins = explode(',',$administrators);
                $validadmins = array();
                foreach ($admins as $admin) {
                    if (empty($admin)) continue;
                    $user = $this->mod()->apiFunc('roles','user','get',array('uname' => trim($admin)));
                    if(!empty($user)) $validadmins[$user['uname']] = $user['uname'];
                }
                $this->mod()->setVar('administrators', serialize($validadmins));
                */
                $this->mod()->setVar('debugmode', $debugmode);
                xarConfigVars::set(null, 'Site.BL.ShowQueries', $show_queries);
                $this->mod()->setVar('suppress_updates', $suppress_updates);
                $this->mod()->setVar('caching', $caching);
                $this->mod()->setVar('twig_support', $twig_support);
                // save to cache if enabled
                xarModVars::cache('dynamicdata');
                break;
        }
        return $data;
    }
}
