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
use Xaraya\Modules\DynamicData\AdminApi;
use sys;

/**
 * dynamicdata admin new function
 * @extends MethodClass<AdminGui>
 */
class NewMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Show add new item form
     * This is a standard function that is called whenever an administrator
     * wishes to create a new module item
     * @return string|void output display string
     * @see AdminGui::new()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();

        $this->var()->check('objectid', $objectid, 'id', 1);
        $this->var()->check('name', $name);
        $this->var()->check('module_id', $module_id, 'id', 182);
        $this->var()->check('itemtype', $itemtype, 'id', 0);
        $this->var()->check('itemid', $itemid, 'isset', 0);
        $this->var()->check('preview', $preview);
        $this->var()->check('join', $join);
        $this->var()->check('table', $table);
        $this->var()->check('template', $template);
        $this->var()->check('notfresh', $notfresh);
        $this->var()->check('tplmodule', $tplmodule, 'str');

        $data = $adminapi->menu();

        // set context if available in function
        $myobject = $this->data()->getObject(
            ['objectid' => $objectid,
                'name'      => $name,
                'moduleid'  => $module_id,
                'itemtype'  => $itemtype,
                'join'      => $join,
                'table'     => $table,
                'itemid'    => $itemid,
                'tplmodule' => $tplmodule,
                'template'  => $template]
        );
        // Security
        if (empty($myobject)) {
            $msg = $this->ml('Data object not found');
            return $this->ctl()->notFound($msg);
        }
        if (!$myobject->checkAccess('create')) {
            $msg = $this->ml('Create #(1) is forbidden', $myobject->label);
            return $this->ctl()->forbidden($msg);
        }

        $args = $myobject->toArray();
        $data['object'] = & $myobject;
        $data['tplmodule'] = $args['tplmodule'];  //TODO: is this needed?

        // Generate a one-time authorisation code for this operation
        $data['authid'] = $this->sec()->genAuthKey();

        // Makes this hooks call explictly from DD - why ???
        ////$modinfo = $this->mod()->getInfo($myobject->moduleid);
        //$modinfo = $this->mod()->getInfo(182);
        $myobject->callHooks('new');
        $data['hooks'] = $myobject->hookoutput;
        $data['context'] ??= $myobject->getContext();

        $this->tpl()->setPageTitle($this->ml('Manage - Create New Item in #(1)', $myobject->label));

        if (file_exists(sys::code() . 'modules/' . $args['tplmodule'] . '/xartemplates/admin-new.xt')
            || file_exists(sys::code() . 'modules/' . $args['tplmodule'] . '/xartemplates/admin-new-' . $args['template'] . '.xt')) {
            return $this->tpl()->module($args['tplmodule'], 'admin', 'new', $data, $args['template']);
        } else {
            return $this->render('new', $data, $args['template']);
        }
    }
}
