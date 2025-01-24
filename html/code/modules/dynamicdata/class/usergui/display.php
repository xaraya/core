<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\DataObject\UserGui;

use Xaraya\DataObject\MethodClass;
use Xaraya\DataObject\UserGui;
use DataObjectFactory;
use xarController;
use xarTpl;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * dynamicdata user display function
 * @extends MethodClass<UserGui>
 */
class DisplayMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * display an item
     * This is a standard function to provide detailed informtion on a single item
     * available from the module.
     * @param array<string,mixed> $args an array of arguments (if called by other modules)
     * @return string|void output display string
     * @see UserGui::display()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!$this->var()->check('objectid', $objectid)) {
            return;
        }
        if (!$this->var()->check('name', $name)) {
            return;
        }
        if (!$this->var()->check('module_id', $moduleid)) {
            return;
        }
        if (!$this->var()->check('itemid', $itemid)) {
            return;
        }
        if (!$this->var()->check('template', $template)) {
            return;
        }
        if (!$this->var()->check('tplmodule', $tplmodule)) {
            return;
        }

        // set context if available in function
        $myobject = $this->data()->getObject(
            ['objectid' => $objectid,
                'name' => $name,
                'itemid'   => $itemid,
                'tplmodule' => $tplmodule]
        );
        if (!isset($myobject)) {
            return;
        }
        if (!$myobject->checkAccess('display')) {
            $msg = $this->ml('Display #(1) is forbidden', $myobject->label);
            return $this->ctl()->forbidden($msg);
        }

        $args = $myobject->toArray();
        $myobject->getItem();

        $data = [];

        // *Now* we can set the data stuff
        $data['object'] = & $myobject;
        $data['objectid'] = $args['objectid'];
        $data['itemid'] = $args['itemid'];

        // Display hooks - not called automatically (yet)
        $myobject->callHooks('display');
        $data['hooks'] = $myobject->hookoutput;
        $data['context'] ??= $myobject->getContext();

        $this->tpl()->setPageTitle($myobject->label);

        // Return the template variables defined in this function
        if (file_exists(sys::code() . 'modules/' . $args['tplmodule'] . '/xartemplates/user-display.xt') ||
            file_exists(sys::code() . 'modules/' . $args['tplmodule'] . '/xartemplates/user-display-' . $args['template'] . '.xt')) {
            return $this->tpl()->module($args['tplmodule'], 'user', 'display', $data, $args['template']);
        } else {
            return $this->tpl()->module('dynamicdata', 'user', 'display', $data, $args['template']);
        }
    }
}
