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
use Xaraya\Modules\DynamicData\UtilApi;
use sys;

sys::import('modules.dynamicdata.method');


/**
 * dynamicdata admin view_static function
 * @extends MethodClass<AdminGui>
 */
class ViewStaticMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Return static table information
     * @see AdminGui::viewStatic()
     */
    public function __invoke(array $args = [])
    {
        /** @var UtilApi $utilapi */
        $utilapi = $this->utilapi();
        // Security
        if (!$this->sec()->checkAccess('AdminDynamicData')) {
            return;
        }

        $this->var()->check('module', $module);
        $this->var()->check('module_id', $module_id);
        $this->var()->check('itemtype', $itemtype);
        $this->var()->check('table', $table, 'isset', '');
        $this->var()->check('newtable', $newtable, 'isset', '');
        $this->var()->check('export', $export, 'isset', 0);

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

        $static = $utilapi->getstatic(['module'   => $module,
            'module_id'    => $module_id,
            'itemtype' => $itemtype,
            'table'    => $table]);

        $metas = $utilapi->getmeta([]);
        $data['tables'] = [];
        foreach ($metas as $name => $value) {
            $data['tables'][] = ['id' => $name, 'name' => $name];
        }
        $data['table'] = $table;

        //xar_debug($static);
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
        $modInfo = $this->mod()->getInfo($module_id);
        $data['module'] = $modInfo['name'];
        $data['itemtype'] = $itemtype;
        $data['authid'] = $this->sec()->genAuthKey();

        $this->tpl()->setPageTemplateName('admin');

        return $data;
    }
}
