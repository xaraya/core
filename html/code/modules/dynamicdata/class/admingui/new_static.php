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
use Exception;
use xarController;
use xarDB;
use xarMod;
use xarSec;
use xarSecurity;
use xarTpl;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * dynamicdata admin new_static function
 * @extends MethodClass<AdminGui>
 */
class NewStaticMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     *
     * @return mixed data array for the template display or output display string if invalid data submitted
     * @todo use context
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!xarSecurity::check('AdminDynamicData')) {
            return;
        }

        $data = ['table' => '', 'confirm' => false];
        if (!$this->var()->fetch('table', 'str:1', $data['table'], '', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!$this->var()->fetch('confirm', 'bool', $data['confirm'], false, xarVar::NOT_REQUIRED)) {
            return;
        }

        $data['object'] = DataObjectFactory::getObject(['name' => 'dynamicdata_tablefields']);
        $data['authid'] = $this->sec()->genAuthKey();

        if ($data['confirm']) {

            // Check for a valid confirmation key
            if (!$this->sec()->confirmAuthKey()) {
                return $this->ctl()->badRequest('bad_author');
            }

            // Get the data from the form
            $isvalid = $data['object']->checkInput();

            if (!$isvalid) {
                // Bad data: redisplay the form with error messages
                $data['context'] ??= $this->getContext();
                return xarTpl::module('dynamicdata', 'admin', 'new_static', $data);
            } else {
                if (empty($data['table'])) {
                    throw new Exception($this->ml('Table name missing'));
                }

                // Good data: create the field
                $options = xarMod::apiFunc('dynamicdata', 'data', 'getdatatypeoptions');
                $query = 'ALTER TABLE ' . $data['table'] . ' ADD ';
                $query .= $data['object']->properties['name']->value . ' ';
                $query .= $options['datatypes'][$data['object']->properties['type']->value] . ' ';
                if ((in_array($data['object']->properties['type']->value, [3,4,5]))) {
                    $query .= $options['attributes'][$data['object']->properties['attributes']->value] . " ";
                }
                $query .= $options['nulls'][$data['object']->properties['null']->value] . " ";
                //                $query .= 'COLLATE ' . $options['collations'][$data['object']->properties['collation']->value] . " ";

                if ($data['object']->properties['type']->value != 6) {
                    if (in_array($data['object']->properties['type']->value, [3,4,5])) {
                        if (is_numeric($data['object']->properties['default']->value)) {
                            $query .= 'default ' . $data['object']->properties['default']->value;
                        }
                    } else {
                        $query .= 'default "' . $data['object']->properties['default']->value . '"';
                    }
                }
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
        }
        return $data;
    }
}
