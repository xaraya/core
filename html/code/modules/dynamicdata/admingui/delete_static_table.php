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

/**
 * dynamicdata admin delete_static_table function
 * @extends MethodClass<AdminGui>
 */
class DeleteStaticTableMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup * @see AdminGui::deleteStaticTable()
     */

    public function __invoke(array $args = [])
    {
        // Security
        if (!$this->sec()->checkAccess('AdminDynamicData')) {
            return;
        }

        $data = ['table' => '', 'confirm' => false];
        $this->var()->find('table', $data['table'], 'str:1', '');
        $this->var()->find('confirm', $data['confirm'], 'bool', false);

        $data['object'] = $this->data()->getObject(['name' => 'dynamicdata_tablefields']);

        $data['tplmodule'] = 'dynamicdata';

        if ($data['confirm']) {

            $query = 'DROP TABLE ' . $data['table'];
            $dbconn = $this->db()->getConn();
            $dbconn->Execute($query);

            // Jump to the next page
            $this->ctl()->redirect($this->mod()->getURL('admin', 'view_static'));
            return true;
        }
        return $data;
    }
}
