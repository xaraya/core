<?php
/**
 * Create a new table field
 *
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 */
sys::import('modules.dynamicdata.class.objects.factory');

/**
 * @return mixed data array for the template display or output display string if invalid data submitted
 * @todo use context
 */
function dynamicdata_admin_new_static(array $args = [], $context = null)
{
    // Security
    if (!xarSecurity::check('AdminDynamicData')) {
        return;
    }

    $data = ['table' => '', 'confirm' => false];
    if (!xarVar::fetch('table', 'str:1', $data['table'], '', xarVar::NOT_REQUIRED)) {
        return;
    }
    if (!xarVar::fetch('confirm', 'bool', $data['confirm'], false, xarVar::NOT_REQUIRED)) {
        return;
    }

    $data['object'] = DataObjectFactory::getObject(['name' => 'dynamicdata_tablefields']);
    $data['authid'] = xarSec::genAuthKey();

    if ($data['confirm']) {

        // Check for a valid confirmation key
        if (!xarSec::confirmAuthKey()) {
            return xarController::badRequest('bad_author', $context);
        }

        // Get the data from the form
        $isvalid = $data['object']->checkInput();

        if (!$isvalid) {
            // Bad data: redisplay the form with error messages
            return xarTpl::module('dynamicdata', 'admin', 'new_static', $data);
        } else {
            if (empty($data['table'])) {
                throw new Exception(xarML('Table name missing'));
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
            xarController::redirect(xarController::URL('dynamicdata', 'admin', 'view_static',
                ['table' => $data['table']]), null, $context);
            return true;
        }
    }
    return $data;
}
