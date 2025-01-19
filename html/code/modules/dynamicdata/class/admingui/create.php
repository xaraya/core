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
use xarSession;
use xarTpl;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * dynamicdata admin create function
 * @extends MethodClass<AdminGui>
 */
class CreateMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * This is a standard function that is called with the results of the
     * form supplied by xarMod::guiFunc('dynamicdata','admin','new') to create a new item
     * @param array<string,mixed> $args
     * with
     *     int    objectid
     *     int    itemid
     *     string preview
     *     string return_url
     *     string join
     *     string table
     *     string template
     *     string tplmodule
     * @return mixed
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        // FIXME: whatever, as long as it doesn't generate Variable "0" should not be empty exceptions
        //        or relies on $myobject or other stuff like that...

        if (!$this->var()->fetch('objectid', 'isset', $objectid, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('itemid', 'isset', $itemid, 0, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!$this->var()->fetch('preview', 'isset', $preview, 0, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!$this->var()->fetch('return_url', 'isset', $return_url, null, xarVar::DONT_SET)) {
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
        if (!$this->var()->fetch('tplmodule', 'isset', $tplmodule, 'dynamicdata', xarVar::NOT_REQUIRED)) {
            return;
        }

        if (!$this->sec()->confirmAuthKey()) {
            return $this->ctl()->badRequest('bad_author');
        }

        // set context if available in function
        $myobject = DataObjectFactory::getObject(
            ['objectid' => $objectid,
                'join'     => $join,
                'table'    => $table,
                'itemid'   => $itemid],
            $this->getContext()
        );

        // Security (Bug:
        if (!$myobject->checkAccess('create')) {
            $msg = $this->ml('Create #(1) is forbidden', $myobject->label);
            return $this->ctl()->forbidden($msg);
        }

        $isvalid = $myobject->checkInput();

        // recover any session var information
        $data = xarMod::apiFunc('dynamicdata', 'user', 'getcontext', ['module' => $tplmodule]);
        extract($data);

        if (!empty($preview) || !$isvalid) {
            $data = array_merge($data, xarMod::apiFunc('dynamicdata', 'admin', 'menu'));

            $data['object'] = $myobject;

            $data['authid'] = $this->sec()->genAuthKey();
            $data['preview'] = $preview;
            if (!empty($return_url)) {
                $data['return_url'] = $return_url;
            }

            // Makes this hooks call explictly from DD - why ???
            ////$modinfo = xarMod::getInfo($myobject->moduleid);
            //$modinfo = xarMod::getInfo(182);
            $myobject->callHooks('new');
            $data['hooks'] = $myobject->hookoutput;
            $data['context'] ??= $myobject->getContext();

            if (!isset($template)) {
                $template = $myobject->name;
            }
            return xarTpl::module($tplmodule, 'admin', 'new', $data, $template);
        }

        $itemid = $myobject->createItem();

        // If we are here then the create is valid: reset the session var
        xarSession::setVar('ddcontext.' . $tplmodule, ['tplmodule' => $tplmodule]);

        if (empty($itemid)) {
            return;
        } // throw back

        if (!empty($return_url)) {
            $this->ctl()->redirect($return_url);
        } elseif (!empty($table)) {
            $this->ctl()->redirect(xarController::URL(
                'dynamicdata',
                'admin',
                'view',
                ['table' => $table]
            ));
        } else {
            $this->ctl()->redirect(xarController::URL(
                'dynamicdata',
                'admin',
                'view',
                ['itemid' => $objectid,
                    'tplmodule' => $tplmodule],
            ));
        }
        return true;
    }
}
