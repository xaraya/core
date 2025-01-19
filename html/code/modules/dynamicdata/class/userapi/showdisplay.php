<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\DataObject\UserApi;

use Xaraya\Modules\MethodClass;
use Xaraya\DataObject\UserApi;
use DataObjectDescriptor;
use DataObjectFactory;
use xarTpl;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

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
            $args['context'] ??= $this->getContext();
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
        $object = DataObjectFactory::getObject($args, $this->getContext());
        if (!$object->checkAccess('display')) {
            return $this->ml('Display #(1) is forbidden', $object->label);
        }

        // we're dealing with a real item, so retrieve the property values
        if (!empty($itemid)) {
            $object->getItem();
        }
        // if we are in preview mode, we need to check for any preview values
        //if (!$this->var()->check('preview', $preview, 'isset',  NULL)) {return;}
        if (!empty($preview)) {
            $object->checkInput();
        }

        return $object->showDisplay($args);
    }
}
