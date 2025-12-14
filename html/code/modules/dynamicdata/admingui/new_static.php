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
use Xaraya\Modules\DynamicData\DataApi;
use Exception;

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
     * @see AdminGui::newStatic()
     */
    public function __invoke(array $args = [])
    {
        /** @var DataApi $dataapi */
        $dataapi = $this->dataapi();
        // Security
        if (!$this->sec()->checkAccess('AdminDynamicData')) {
            return;
        }

        $data = ['table' => '', 'confirm' => false];
        $this->var()->find('table', $data['table'], 'str:1', '');
        $this->var()->find('confirm', $data['confirm'], 'bool', false);

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
                return $data;
            } else {
                if (empty($data['table'])) {
                    throw new Exception($this->ml('Table name missing'));
                }

                // Good data: create the field
                $options = $dataapi->getdatatypeoptions();
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
        }
        return $data;
    }
}
