<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\DynamicData\UserGui;

use Xaraya\Modules\DynamicData\MethodClass;
use Xaraya\Modules\DynamicData\UserGui;
use sys;

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

        $this->var()->check('objectid', $objectid);
        $this->var()->check('name', $name);
        $this->var()->check('module_id', $moduleid);
        $this->var()->check('itemid', $itemid);
        $this->var()->check('template', $template);
        $this->var()->check('tplmodule', $tplmodule);

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
        if (file_exists(sys::code() . 'modules/' . $args['tplmodule'] . '/xartemplates/user-display.xt')
            || file_exists(sys::code() . 'modules/' . $args['tplmodule'] . '/xartemplates/user-display-' . $args['template'] . '.xt')) {
            return $this->tpl()->module($args['tplmodule'], 'user', 'display', $data, $args['template']);
        } else {
            return $this->render('display', $data, $args['template']);
        }
    }
}
