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
use DataObjectFactory;
use xarController;
use xarMod;
use xarSec;
use xarTpl;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

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
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!$this->var()->fetch('objectid', 'id', $objectid, 1, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('name', 'isset', $name, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('module_id', 'id', $module_id, 182, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('itemtype', 'id', $itemtype, 0, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('itemid', 'isset', $itemid, 0, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('preview', 'isset', $preview, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('join', 'isset', $join, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('table', 'isset', $table, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('template', 'isset', $template, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('notfresh', 'isset', $notfresh, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('tplmodule', 'str', $tplmodule, null, xarVar::DONT_SET)) {
            return;
        }

        $data = xarMod::apiFunc('dynamicdata', 'admin', 'menu');

        // set context if available in function
        $myobject = DataObjectFactory::getObject(
            ['objectid' => $objectid,
                'name'      => $name,
                'moduleid'  => $module_id,
                'itemtype'  => $itemtype,
                'join'      => $join,
                'table'     => $table,
                'itemid'    => $itemid,
                'tplmodule' => $tplmodule,
                'template'  => $template],
            $this->getContext()
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
        ////$modinfo = xarMod::getInfo($myobject->moduleid);
        //$modinfo = xarMod::getInfo(182);
        $myobject->callHooks('new');
        $data['hooks'] = $myobject->hookoutput;
        $data['context'] ??= $myobject->getContext();

        xarTpl::setPageTitle($this->ml('Manage - Create New Item in #(1)', $myobject->label));

        if (file_exists(sys::code() . 'modules/' . $args['tplmodule'] . '/xartemplates/admin-new.xt') ||
            file_exists(sys::code() . 'modules/' . $args['tplmodule'] . '/xartemplates/admin-new-' . $args['template'] . '.xt')) {
            return xarTpl::module($args['tplmodule'], 'admin', 'new', $data, $args['template']);
        } else {
            return xarTpl::module('dynamicdata', 'admin', 'new', $data, $args['template']);
        }
    }
}
