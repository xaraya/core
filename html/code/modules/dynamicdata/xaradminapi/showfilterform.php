<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Show an input form in a template
 *
 * @param array<string, mixed> $args array of optional parameters containing the item or fields to show
 * @return string|void output display string
 */
function dynamicdata_adminapi_showfilterform(array $args = [], $context = null)
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
        return xarTpl::module(
            'dynamicdata',
            'admin',
            'showfilterform',
            ['fields' => $fields,
            'layout' => $layout],
            $template
        );
    }

    // try getting the item id via input variables if necessary
    if (!isset($itemid) || !is_numeric($itemid)) {
        if (!xarVar::fetch('itemid', 'isset', $args['itemid'], null, xarVar::DONT_SET)) {
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
    $object = DataObjectFactory::getObject($args, $context);
    if (empty($itemid)) {
        if (!$object->checkAccess('create')) {
            return xarML('Create #(1) is forbidden', $object->label);
        }
    } else {
        if (!$object->checkAccess('update')) {
            return xarML('Update #(1) is forbidden', $object->label);
        }
    }

    if (!empty($itemid)) {
        $object->getItem();
    }
    // if we are in preview mode, we need to check for any preview values
    //if (!xarVar::fetch('preview', 'isset', $preview,  NULL, xarVar::DONT_SET)) {return;}
    if (!empty($preview)) {
        $object->checkInput();
    }

    return $object->showFilterForm($args);
}
