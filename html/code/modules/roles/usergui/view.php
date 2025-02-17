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
use xarController;
use xarMod;
use xarModVars;
use xarRoles;
use xarSecurity;
use xarTpl;
use xarVar;
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
        if (!xarSecurity::check('ViewRoles')) {
            return;
        }

        // members list disabled? only show to roles admins
        if ((bool) xarModVars::get('roles', 'displayrolelist') == false && !xarSecurity::check('AdminRoles', 0)) {
            xarController::redirect(xarController::URL('roles', 'user', 'main'), null, $this->getContext());
        }
        //    extract($args);

        if (!xarVar::fetch('startnum', 'int:1', $args['startnum'], null, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('itemtype', 'int', $args['itemtype'], xarRoles::ROLES_USERTYPE, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('search', 'str:1:100', $args['search'], null, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('order', 'str', $args['order'], null, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('include', 'str', $args['include'], null, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('exclude', 'str', $args['exclude'], null, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('tplmodule', 'str', $args['tplmodule'], 'roles', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('template', 'str', $args['template'], '', xarVar::NOT_REQUIRED)) {
            return;
        }

        $data['items'] = [];
        $data['pager'] = '';

        $roles = $userapi->getallroles($args);
        $items = $roles['nativeitems'];
        $objectlists = $roles['dditems'];

        // keep track of the selected id's

        $itemlabels = [xarML('ID'),xarML('Name'),xarML('Itemtype'),xarML('Users'),xarML('User Name'),xarML('Password'),xarML('Email'),xarML('Date Registered'),xarML('State'),xarML('Validation Code'),xarML('Created By'),];
        $ddlabels = xarMod::apiFunc('dynamicdata', 'user', 'getitemfields', ['modid' => 27, 'itemtype' => $args['itemtype']]);
        foreach ($ddlabels as $label) {
            $itemlabels[] = $label['label'];
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
            $numitems = (int) xarModVars::get('roles', 'items_per_page');
        }

        $numitems = (int) xarModVars::get('roles', 'items_per_page');
        $pagerfilter['order'] = $data['order'];
        $pagerfilter['search'] = $data['search'];
        $pagerfilter['startnum'] = '%%';

        $data['itemsperpage'] = $numitems;
        $data['urltemplate'] = xarController::URL('roles', 'user', 'view', $pagerfilter);
        $data['urlitemmatch'] = '%%';

        $data['context'] ??= $this->getContext();
        return xarTpl::module($args['tplmodule'], 'user', 'view', $data, $args['template']);
    }
}
