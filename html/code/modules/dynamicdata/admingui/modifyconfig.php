<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\DynamicData\AdminGui;

use Xaraya\Modules\DynamicData\MethodClass;
use Xaraya\Modules\DynamicData\AdminGui;
use xarModVars;
use sys;

sys::import('modules.dynamicdata.method');


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
        $this->var()->find('phase', $phase, 'str:1:100', 'modify');
        $this->var()->find('tab', $data['tab'], 'str:1', 'general');

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
                $this->var()->find('debugmode', $debugmode, 'checkbox', $this->mod()->getVar('debugmode'));
                $this->var()->find('show_queries', $show_queries, 'checkbox', $this->config()->getVar('Site.BL.ShowQueries'));
                $this->var()->find('suppress_updates', $suppress_updates, 'checkbox', false);
                // $this->var()->find('administrators', $administrators, 'str', '');
                $this->var()->find('caching', $caching, 'checkbox', $this->mod()->getVar('caching'));
                $this->var()->find('twig_support', $twig_support, 'checkbox', false);

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
                $this->config()->setVar('Site.BL.ShowQueries', $show_queries);
                $this->mod()->setVar('suppress_updates', $suppress_updates);
                $this->mod()->setVar('caching', $caching);
                $this->mod()->setVar('twig_support', $twig_support);
                // save to cache if enabled
                $this->mod()->cacheVars(__METHOD__);
                break;
        }
        return $data;
    }
}
