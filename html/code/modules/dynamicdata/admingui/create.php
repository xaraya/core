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
use Xaraya\Modules\DynamicData\UserApi;
use Xaraya\Modules\DynamicData\AdminApi;

/**
 * dynamicdata admin create function
 * @extends MethodClass<AdminGui>
 */
class CreateMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * This is a standard function that is called with the results of the
     * form supplied by $admingui->new() to create a new item
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
     * @see AdminGui::create()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        /** @var AdminGui $admingui */
        $admingui = $this->admingui();

        // FIXME: whatever, as long as it doesn't generate Variable "0" should not be empty exceptions
        //        or relies on $myobject or other stuff like that...

        $this->var()->check('objectid', $objectid);
        $this->var()->check('itemid', $itemid, 'isset', 0);
        $this->var()->check('preview', $preview, 'isset', 0);
        $this->var()->check('return_url', $return_url);
        $this->var()->check('join', $join);
        $this->var()->check('table', $table);
        $this->var()->check('template', $template);
        $this->var()->check('tplmodule', $tplmodule, 'isset', 'dynamicdata');

        if (!$this->sec()->confirmAuthKey()) {
            return $this->ctl()->badRequest('bad_author');
        }

        // set context if available in function
        $myobject = $this->data()->getObject(
            ['objectid' => $objectid,
                'join'     => $join,
                'table'    => $table,
                'itemid'   => $itemid]
        );

        // Security (Bug:
        if (!$myobject->checkAccess('create')) {
            $msg = $this->ml('Create #(1) is forbidden', $myobject->label);
            return $this->ctl()->forbidden($msg);
        }

        $isvalid = $myobject->checkInput();

        // recover any session var information
        $data = $userapi->sessioncontext(['module' => $tplmodule]);
        extract($data);

        if (!empty($preview) || !$isvalid) {
            $data = array_merge($data, $adminapi->menu());

            $data['object'] = $myobject;

            $data['authid'] = $this->sec()->genAuthKey();
            $data['preview'] = $preview;
            if (!empty($return_url)) {
                $data['return_url'] = $return_url;
            }

            // Makes this hooks call explictly from DD - why ???
            ////$modinfo = $this->mod()->getInfo($myobject->moduleid);
            //$modinfo = $this->mod()->getInfo(182);
            $myobject->callHooks('new');
            $data['hooks'] = $myobject->hookoutput;
            $data['context'] ??= $myobject->getContext();

            if (!isset($template)) {
                $template = $myobject->name;
            }
            return $this->tpl()->module($tplmodule, 'admin', 'new', $data, $template);
        }

        $itemid = $myobject->createItem();

        // If we are here then the create is valid: reset the session var
        $this->session()->setVar('ddcontext.' . $tplmodule, ['tplmodule' => $tplmodule]);

        if (empty($itemid)) {
            return;
        } // throw back

        if (!empty($return_url)) {
            $this->ctl()->redirect($return_url);
        } elseif (!empty($table)) {
            $this->ctl()->redirect($this->mod()->getURL(
                'admin',
                'view',
                ['table' => $table]
            ));
        } else {
            $this->ctl()->redirect($this->mod()->getURL(
                'admin',
                'view',
                ['itemid' => $objectid,
                    'tplmodule' => $tplmodule],
            ));
        }
        return true;
    }
}
