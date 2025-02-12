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

use Xaraya\DataObject\MethodClass;
use Xaraya\DataObject\AdminGui;
use Xaraya\DataObject\DataApi;
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
 * dynamicdata admin modify_static function
 * @extends MethodClass<AdminGui>
 */
class ModifyStaticMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     *
     * @return mixed data array for the template display or output display string if invalid data submitted
     * @todo use context
     * @see AdminGui::modifyStatic()
     */
    public function __invoke(array $args = [])
    {
        /** @var DataApi $dataapi */
        $dataapi = $this->dataapi();
        // Security
        if (!$this->sec()->checkAccess('EditDynamicData')) {
            return;
        }

        $data = ['table' => '', 'field' => '', 'oldname' => '', 'confirm' => false];
        if (!$this->var()->find('table', $data['table'], 'str:1', '')) {
            return;
        }
        if (!$this->var()->find('field', $data['field'], 'str:1', '')) {
            return;
        }
        if (!$this->var()->find('oldname', $data['oldname'], 'str:1', '')) {
            return;
        }
        if (!$this->var()->find('confirm', $data['confirm'], 'bool', false)) {
            return;
        }

        $data['object'] = $this->data()->getObject(['name' => 'dynamicdata_tablefields']);
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
                return $this->tpl()->module('dynamicdata', 'admin', 'modify_static', $data);
            } else {
                if (empty($data['table'])) {
                    throw new Exception($this->ml('Table name missing'));
                }
                if (empty($data['oldname'])) {
                    throw new Exception($this->ml('Previous field name missing'));
                }

                // Good data: create the field
                $options = $dataapi->getdatatypeoptions();
                $query = 'ALTER TABLE ' . $data['table'] . ' CHANGE COLUMN `' . $data['oldname'] . '` `';
                $query .= $data['object']->properties['name']->value . '` ';
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
                $dbconn = $this->db()->getConn();
                $dbconn->Execute($query);

                // Jump to the next page
                $this->ctl()->redirect($this->mod()->getURL(
                    'admin',
                    'view_static',
                    ['table' => $data['table']]
                ));
                return true;
            }
        } else {
            $dbconn = $this->db()->getConn();
            $dbInfo = $dbconn->getDatabaseInfo();
            $tableinfo = $dbInfo->getTable($data['table']);
            $fieldinfo = $tableinfo->getColumn($data['field']);
            $fieldargs = [
                'name' => $fieldinfo->getName(),
                'type' => $fieldinfo->getType(),
                'nativetype' => $fieldinfo->getNativeType(),
                'size' => $fieldinfo->getSize(),
                'scale' => $fieldinfo->getScale(),
                'default' => $fieldinfo->getDefaultValue(),
                'null' => $fieldinfo->isNullable(),
                'autoincrement' => $fieldinfo->isAutoIncrement(),
                'vendor' => $fieldinfo->getVendorSpecificInfo(),
            ];

            // This is a bit dodgy, but lets first see how many distinct datatypes we actually want to allow before we get too fancy here
            $fieldargs['attributes'] = 0;
            switch ($fieldargs['size']) {
                case 3:
                    $fieldargs['attributes'] = 1;
                    // no break
                case 4:
                    $fieldargs['type'] = 3;
                    break;
                case 10:
                    $fieldargs['attributes'] = 1;
                    // no break
                case 11:
                    $fieldargs['type'] = 4;
                    break;
                case 64:
                    $fieldargs['type'] = 1;
                    break;
                case 254:
                    $fieldargs['type'] = 2;
                    break;
            }
            if ($fieldargs['nativetype'] == 'text') {
                $fieldargs['type'] = 6;
            }
            if (empty($fieldargs['nativetype'])) {
                $fieldargs['type'] = 5;
            }

            $data['object']->setFieldValues($fieldargs);
            $data['oldname'] = $fieldargs['name'];

        }
        return $data;
    }
}
