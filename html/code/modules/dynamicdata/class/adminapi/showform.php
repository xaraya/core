<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\DataObject\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\DataObject\AdminApi;
use DataObjectDescriptor;
use DataObjectFactory;
use xarTpl;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * dynamicdata adminapi showform function
 * @extends MethodClass<AdminApi>
 */
class ShowformMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Show an input form in a template
     * @param array<string,mixed> $args array of optional parameters containing the item or fields to show
     * @return string|void output display string
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        // Support the objectname parameter in the data-form tag
        if (isset($args['objectname'])) {
            $args['name'] = $args['objectname'];
        }

        $args['fallbackmodule'] = 'current';
        $descriptor = new DataObjectDescriptor($args);
        $args = $descriptor->getArgs();

        // optional layout for the template
        if (empty($layout)) {
            $layout = 'default';
        }
        // or optional template, if you want e.g. to handle individual fields
        // differently for a specific module / item type
        if (empty($template)) {
            $template = '';
        }

        // we got everything via template parameters
        if (isset($fields) && is_array($fields) && count($fields) > 0) {
            return $this->tpl()->module(
                'dynamicdata',
                'admin',
                'showform',
                ['fields' => $fields,
                    'layout' => $layout,
                    'context' => $this->getContext()],
                $template
            );
        }

        // try getting the item id via input variables if necessary
        if (!isset($itemid) || !is_numeric($itemid)) {
            if (!$this->var()->check('itemid', $args['itemid'])) {
                return;
            }
        }

        // check the optional field list
        if (!empty($fieldlist)) {
            // support comma-separated field list
            if (is_string($fieldlist)) {
                $args['fieldlist'] = explode(',', $fieldlist);
                // and array of fields
            } elseif (is_array($fieldlist)) {
                $args['fieldlist'] = $fieldlist;
            }
        } else {
            $args['fieldlist'] = null;
        }

        // set context if available in function
        $object = DataObjectFactory::getObject($args, $this->getContext());
        if (empty($itemid)) {
            if (!$object->checkAccess('create')) {
                return $this->ml('Create #(1) is forbidden', $object->label);
            }
        } else {
            if (!$object->checkAccess('update')) {
                return $this->ml('Update #(1) is forbidden', $object->label);
            }
        }

        if (!empty($itemid)) {
            $object->getItem();
        }
        // if we are in preview mode, we need to check for any preview values
        //if (!$this->var()->check('preview', $preview)) {return;}
        if (!empty($preview)) {
            $object->checkInput();
        }

        return $object->showForm($args);
    }
}
