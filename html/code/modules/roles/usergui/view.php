<?php

/**
 * @package modules\roles
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Roles\UserGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Roles\UserGui;
use Xaraya\Modules\Roles\UserApi;
use xarRoles;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles user view function
 * @extends MethodClass<UserGui>
 */
class ViewMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * view users
     * @return string|void output display string
     * @see UserGui::view()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        if (!$this->sec()->checkAccess('ViewRoles')) {
            return;
        }

        // members list disabled? only show to roles admins
        if ((bool) $this->mod()->getVar('displayrolelist') == false && !$this->sec()->checkAccess('AdminRoles', 0)) {
            $this->ctl()->redirect($this->ctl()->getModuleURL('roles', 'user', 'main'));
            return true;
        }
        //    extract($args);

        $this->var()->find('startnum', $args['startnum'], 'int:1', null);
        $this->var()->find('itemtype', $args['itemtype'], 'int', xarRoles::ROLES_USERTYPE);
        $this->var()->find('search', $args['search'], 'str:1:100', null);
        $this->var()->find('order', $args['order'], 'str', null);
        $this->var()->find('include', $args['include'], 'str', null);
        $this->var()->find('exclude', $args['exclude'], 'str', null);
        $this->var()->find('tplmodule', $args['tplmodule'], 'str', 'roles');
        $this->var()->find('template', $args['template'], 'str', '');

        $data['items'] = [];
        $data['pager'] = '';

        $roles = $userapi->getallroles($args);
        $items = $roles['nativeitems'];
        // @todo template expects an objectlist, but getallroles() returns items array
        $objectlists = $roles['dditems'];

        // keep track of the selected id's

        $itemlabels = [$this->ml('ID'),$this->ml('Name'),$this->ml('Itemtype'),$this->ml('Users'),$this->ml('User Name'),$this->ml('Password'),$this->ml('Email'),$this->ml('Date Registered'),$this->ml('State'),$this->ml('Validation Code'),$this->ml('Created By'),];
        $ddlabels = $this->mod()->apiFunc('dynamicdata', 'user', 'getitemfields', ['modid' => 27, 'itemtype' => $args['itemtype']]);
        foreach ($ddlabels as $field => $label) {
            $itemlabels[] = $label;
        }

        $data['total'] = count($items);
        $data['itemtype'] = $args['itemtype'];
        $types = $userapi->getitemtypes();
        $data['itemtypename'] = $types[$data['itemtype']]['label'];
        $data['items'] = $items;
        $data['objectlists'] = [$objectlists];
        $data['itemlabels'] = $itemlabels;
        if (!isset($order)) {
            $data['order'] = 'name';
        }
        if (!isset($search)) {
            $data['search'] = '';
        }
        $data['startnum'] = (!isset($args['startnum'])) ? 1 : $args['startnum'];
        if (!isset($numitems)) {
            $numitems = (int) $this->mod()->getVar('items_per_page');
        }

        $numitems = (int) $this->mod()->getVar('items_per_page');
        $pagerfilter['order'] = $data['order'];
        $pagerfilter['search'] = $data['search'];
        $pagerfilter['startnum'] = '%%';

        $data['itemsperpage'] = $numitems;
        $data['urltemplate'] = $this->ctl()->getModuleURL('roles', 'user', 'view', $pagerfilter);
        $data['urlitemmatch'] = '%%';

        $data['context'] ??= $this->getContext();
        return $this->tpl()->module($args['tplmodule'], 'user', 'view', $data, $args['template']);
    }
}
