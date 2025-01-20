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
use xarDB;
use xarSecurity;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * dynamicdata admin delete_static_table function
 * @extends MethodClass<AdminGui>
 */
class DeleteStaticTableMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    public function __invoke(array $args = [])
    {
        // Security
        if (!$this->sec()->checkAccess('AdminDynamicData')) {
            return;
        }

        $data = ['table' => '', 'confirm' => false];
        if (!$this->var()->find('table', $data['table'], 'str:1', '')) {
            return;
        }
        if (!$this->var()->find('confirm', $data['confirm'], 'bool', false)) {
            return;
        }

        $data['object'] = DataObjectFactory::getObject(['name' => 'dynamicdata_tablefields']);

        $data['tplmodule'] = 'dynamicdata';

        if ($data['confirm']) {

            $query = 'DROP TABLE ' . $data['table'];
            $dbconn = $this->db()->getConn();
            $dbconn->Execute($query);

            // Jump to the next page
            $this->ctl()->redirect(xarController::URL('dynamicdata', 'admin', 'view_static'));
            return true;
        }
        return $data;
    }
}
