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
 * dynamicdata admin rename_static_table function
 * @extends MethodClass<AdminGui>
 */
class RenameStaticTableMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    public function __invoke(array $args = [])
    {
        // Security
        if (!xarSecurity::check('AdminDynamicData')) {
            return;
        }

        $data = ['table' => '', 'newtable' => '', 'confirm' => false];
        if (!$this->var()->fetch('table', 'str:1', $data['table'], '', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!$this->var()->fetch('newtable', 'str:1', $data['newtable'], '', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!$this->var()->fetch('confirm', 'bool', $data['confirm'], false, xarVar::NOT_REQUIRED)) {
            return;
        }

        $data['object'] = DataObjectFactory::getObject(['name' => 'dynamicdata_tablefields']);

        $data['tplmodule'] = 'dynamicdata';

        if ($data['confirm']) {
            if (empty($data['newtable'])) {
                $this->ctl()->redirect(xarController::URL(
                    'dynamicdata',
                    'admin',
                    'view_static',
                    ['table' => $data['table']]
                ));
            }
            $query = 'RENAME TABLE ' . $data['table'] . ' TO ' . $data['newtable'];
            $dbconn = xarDB::getConn();
            $dbconn->Execute($query);

            // Jump to the next page
            $this->ctl()->redirect(xarController::URL(
                'dynamicdata',
                'admin',
                'view_static',
                ['table' => $data['newtable']]
            ));
            return true;
        }
        return $data;
    }
}
