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
use DataObjectFactory;
use xarController;
use xarDB;
use xarSecurity;
use xarVar;
use sys;

sys::import('modules.dynamicdata.method');


/**
 * dynamicdata admin rename_static_table function
 * @extends MethodClass<AdminGui>
 */
class RenameStaticTableMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup * @see AdminGui::renameStaticTable()
     */

    public function __invoke(array $args = [])
    {
        // Security
        if (!$this->sec()->checkAccess('AdminDynamicData')) {
            return;
        }

        $data = ['table' => '', 'newtable' => '', 'confirm' => false];
        if (!$this->var()->find('table', $data['table'], 'str:1', '')) {
            return;
        }
        if (!$this->var()->find('newtable', $data['newtable'], 'str:1', '')) {
            return;
        }
        if (!$this->var()->find('confirm', $data['confirm'], 'bool', false)) {
            return;
        }

        $data['object'] = $this->data()->getObject(['name' => 'dynamicdata_tablefields']);

        $data['tplmodule'] = 'dynamicdata';

        if ($data['confirm']) {
            if (empty($data['newtable'])) {
                $this->ctl()->redirect($this->mod()->getURL(
                    'admin',
                    'view_static',
                    ['table' => $data['table']]
                ));
            }
            $query = 'RENAME TABLE ' . $data['table'] . ' TO ' . $data['newtable'];
            $dbconn = $this->db()->getConn();
            $dbconn->Execute($query);

            // Jump to the next page
            $this->ctl()->redirect($this->mod()->getURL(
                'admin',
                'view_static',
                ['table' => $data['newtable']]
            ));
            return true;
        }
        return $data;
    }
}
