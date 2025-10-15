<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\DynamicData\UserApi;

use Xaraya\Modules\DynamicData\MethodClass;
use Xaraya\Modules\DynamicData\UserApi;
use DataObjectDescriptor;
use sys;

sys::import('modules.dynamicdata.method');


/**
 * dynamicdata userapi showdisplay function
 * @extends MethodClass<UserApi>
 */
class ShowdisplayMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Display an item in a template
     * @param array<string,mixed> $args array of optional parameters<br/>
     * @var array<mixed> $args array containing the item or fields to show
     * @return string output display string
     * @see UserApi::showdisplay()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        $args['fallbackmodule'] = 'current';
        $descriptor = new DataObjectDescriptor($args);
        $args = $descriptor->getArgs();
        if (empty($template)) {
            $template = '';
        }

        // we got everything via template parameters
        if (isset($fields) && is_array($fields) && count($fields) > 0) {
            return $this->tpl()->module(
                'dynamicdata',
                'user',
                'showdisplay',
                $args,
                $template
            );
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
        $object = $this->data()->getObject($args);
        if (!$object->checkAccess('display')) {
            return $this->ml('Display #(1) is forbidden', $object->label);
        }

        // we're dealing with a real item, so retrieve the property values
        if (!empty($itemid)) {
            $object->getItem();
        }
        // if we are in preview mode, we need to check for any preview values
        //$this->var()->check('preview', $preview);
        if (!empty($preview)) {
            $object->checkInput();
        }

        return $object->showDisplay($args);
    }
}
