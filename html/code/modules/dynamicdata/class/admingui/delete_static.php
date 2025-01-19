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
use xarSec;
use xarSecurity;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * dynamicdata admin delete_static function
 * @extends MethodClass<AdminGui>
 */
class DeleteStaticMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    public function __invoke(array $args = [])
    {
        //Security
        if (!$this->sec()->checkAccess('AdminDynamicData')) {
            return;
        }

        $data = ['table' => '', 'field' => '', 'confirm' => false];
        if (!$this->var()->find('table', $data['table'], 'str:1', '')) {
            return;
        }
        if (!$this->var()->find('field', $data['field'], 'str:1', '')) {
            return;
        }
        if (!$this->var()->find('confirm', $data['confirm'], 'bool', false)) {
            return;
        }

        $data['object'] = DataObjectFactory::getObject(['name' => 'dynamicdata_tablefields']);

        $data['tplmodule'] = 'dynamicdata';
        $data['authid'] = $this->sec()->genAuthKey('dynamicdata');

        if ($data['confirm']) {

            // Check for a valid confirmation key
            //            if(!$this->sec()->confirmAuthKey()) return;

            $query = 'ALTER TABLE ' . $data['table'] . ' DROP COLUMN ' . $data['field'];
            $dbconn = xarDB::getConn();
            $dbconn->Execute($query);

            // Jump to the next page
            $this->ctl()->redirect(xarController::URL(
                'dynamicdata',
                'admin',
                'view_static',
                ['table' => $data['table']]
            ));
            return true;
        }
        return $data;
    }
}
