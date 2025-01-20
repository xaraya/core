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
use xarDB;
use xarMod;
use xarSec;
use xarSecurity;
use xarTpl;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * dynamicdata admin view_static function
 * @extends MethodClass<AdminGui>
 */
class ViewStaticMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Return static table information
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!$this->sec()->checkAccess('AdminDynamicData')) {
            return;
        }

        if (!$this->var()->check('module', $module)) {
            return;
        }
        if (!$this->var()->check('module_id', $module_id)) {
            return;
        }
        if (!$this->var()->check('itemtype', $itemtype)) {
            return;
        }
        if (!$this->var()->check('table', $table, 'isset', '')) {
            return;
        }
        if (!$this->var()->check('newtable', $newtable, 'isset', '')) {
            return;
        }
        if (!$this->var()->check('export', $export, 'isset', 0)) {
            return;
        }

        extract($args);

        if (!empty($newtable)) {
            $query = "CREATE TABLE " . $newtable . " (
              id integer unsigned NOT NULL auto_increment,
              PRIMARY KEY  (id))";
            $dbconn = $this->db()->getConn();
            $dbconn->Execute($query);
            $table = $newtable;
        }

        $data = [];
        $data['menutitle'] = $this->ml('Dynamic Data Utilities');

        $static = xarMod::apiFunc(
            'dynamicdata',
            'util',
            'getstatic',
            ['module'   => $module,
                'module_id'    => $module_id,
                'itemtype' => $itemtype,
                'table'    => $table],
            $this->getContext()
        );

        $metas = xarMod::apiFunc('dynamicdata', 'util', 'getmeta', [], $this->getContext());
        $data['tables'] = [];
        foreach ($metas as $name => $value) {
            $data['tables'][] = ['id' => $name, 'name' => $name];
        }
        $data['table'] = $table;

        //debug($static);
        if (!isset($static) || $static == false) {
            $data['tabledata'] = [];
        } else {
            $data['tabledata'] = [];
            foreach ($static as $field) {
                if (preg_match('/^(\w+)\.(\w+)$/', $field['source'], $matches)) {
                    $table = $matches[1];
                    $data['tabledata'][$table][$field['name']] = $field;
                }
            }
        }

        $data['export'] = $export;
        if (!isset($module_id) || $module_id == 0) {
            $module_id = 182;
        }
        $data['module_id'] = $module_id;
        $modInfo = xarMod::getInfo($module_id);
        $data['module'] = $modInfo['name'];
        $data['itemtype'] = $itemtype;
        $data['authid'] = $this->sec()->genAuthKey();

        $this->tpl()->setPageTemplateName('admin');

        return $data;
    }
}
