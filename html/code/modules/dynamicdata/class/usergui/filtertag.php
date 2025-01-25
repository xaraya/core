<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\DataObject\UserGui;

use Xaraya\DataObject\MethodClass;
use Xaraya\DataObject\UserGui;
use DataObjectFactory;
use Exception;
use Query;
use xarController;
use xarServer;
use xarSession;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * dynamicdata user filtertag function
 * @extends MethodClass<UserGui>
 */
class FiltertagMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     *
     * @param array<string,mixed> $args
     * @return array|bool|void
     * @see UserGui::filtertag()
     */
    public function __invoke(array $args = [])
    {
        if (!$this->var()->find('filter_submitted', $filter_submitted, 'int:0', 0)) {
            return;
        }

        if ($filter_submitted) {
            if (!$this->var()->find('objectname', $objectname, 'str', '')) {
                return;
            }
            if (!$this->var()->find('filtername', $filtername, 'str', '')) {
                return;
            }
            if (!$this->var()->find('return_url', $return_url, 'str', '')) {
                return;
            }
            if (!$this->var()->find('name', $names, 'array', [])) {
                return;
            }
            if (!$this->var()->find('source', $source, 'array', [])) {
                return;
            }
            if (!$this->var()->find('op', $op, 'array', [])) {
                return;
            }

            // Get an instance of the dataobject so that we can get at the dataproperties' checkInput() method
            $object = $this->data()->getObject(['name' => $objectname]);

            sys::import('xaraya.structures.query');
            $q = new Query();
            foreach ($names as $name) {
                // Get the value of a property from the template
                $object->properties[$name]->checkInput("value_" . $name);
                $thisvalue = $object->properties[$name]->value;

                if (empty($op[$name])) {
                    continue;
                }

                switch ($op[$name]) {
                    case 'eqempty' :
                        $q->eq($source[$name], '');
                        break;
                    case 'neempty' :
                        $q->ne($source[$name], '');
                        break;
                    case 'null' :
                        $q->eq($source[$name], null);
                        break;
                    case 'notnull' :
                        $q->ne($source[$name], null);
                        break;
                    case 'like' :
                        $q->like($source[$name], '%' . $thisvalue . '%');
                        break;
                    case 'notlike' :
                        $q->notlike($source[$name], '%' . $thisvalue . '%');
                        break;
                    case 'regex' :
                        // Ignore empty an empty field here
                        if (empty($thisvalue)) {
                            break;
                        }
                        $q->regex($source[$name], $thisvalue);
                        break;
                    default:
                        $q->{$op[$name]}($source[$name], $thisvalue);
                        break;
                }
            }

            // Save the conditions in a session var. Perhaps also in some cache?
            if (empty($filtername)) {
                $filtername = $objectname;
            }
            xarSession::setVar('DynamicData.Filter.' . $filtername, serialize($q));

            // Redirect to the next page
            $this->ctl()->redirect($return_url);
            return true;

        } else {
            // Make sure we have a dataobject
            if (!isset($args['object'])) {
                if (isset($args['objectname'])) {
                    $args['object'] = $this->data()->getObject(['name' => $args['objectname']]);
                } else {
                    throw new Exception('Missing $object for filter tag');
                }
            }

            // Check if a fieldlist was passed
            if (isset($args['fieldlist']) && !empty($args['fieldlist'])) {
                // Support both strings and arrays for the fieldlist
                if (!is_array($args['fieldlist'])) {
                    $args['fieldlist'] = explode(',', $args['fieldlist']);
                }
                // Remove any unwanted delimiters, spaces etc.
                foreach ($args['fieldlist'] as $k => $v) {
                    $args['fieldlist'][$k] = trim($v);
                }
            } else {
                $args['fieldlist'] = $args['object']->getFieldList();
            }
            $data['fieldlist'] = $args['fieldlist'];

            if (empty($args['filtername'])) {
                $args['filtername'] = $args['object']->name;
            }
            $filter = @unserialize(xarSession::getVar('DynamicData.Filter.' . $args['filtername']) ?? '');
            if (empty($filter)) {
                $filter = [];
            }
            $values = [];
            $ops    = [];
            if (is_object($filter)) {
                foreach ($filter->conditions as $condition) {
                    $values[$condition['field1']] = trim($condition['field2'], "%");
                    $ops[$condition['field1']]    = $this->transform_operator($condition['op']);
                }
            }

            // Winnow the properties to be used according to the fieldlist, and add the information from any previous filter
            $data['properties'] = [];
            $data['valuelist']  = [];
            $data['oplist']     = [];
            $properties = $args['object']->getProperties();
            foreach ($properties as $name => $property) {
                if (!empty($args['fieldlist']) && !in_array($name, $args['fieldlist'])) {
                    continue;
                }
                $property->value = $property->defaultvalue;
                $data['properties'][$name] = & $property;
                if (isset($values[$property->source])) {
                    $data['valuelist'][$name]  = $values[$property->source];
                }
                if (isset($ops[$property->source])) {
                    $data['oplist'][$name]     = $ops[$property->source];
                }
            }
            // This is the URL we will redirect to when we have submitted
            if (!isset($args['return_url'])) {
                $args['return_url'] = $this->ctl()->getCurrentURL();
            }
            // This is the label for the submit button in the template
            if (!isset($args['button'])) {
                $args['button'] = $this->ml('Submit');
            }

            $data['button'] = $args['button'];
            $data['return_url'] = $args['return_url'];
            $data['objectname'] = $args['object']->name;
            $data['object']     = & $args['object'];
            $data['filtername'] = $args['filtername'];
        }
        return $data;
    }

    /**
     * Summary of transform_operator
     * @param string $op
     * @return string
     */
    public function transform_operator($op)
    {
        $oparray = [
            '='        => 'eq',
            '!='       => 'ne',
            '>'        => 'gt',
            '>='       => 'ge',
            '<'        => 'lt',
            '<='       => 'le',
            'LIKE'     => 'like',
            'NOT LIKE' => 'notlike',
        ];
        return $oparray[$op];
    }
}
