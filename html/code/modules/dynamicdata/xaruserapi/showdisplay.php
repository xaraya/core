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
 * Display an item in a template
 *
 * @param array<string, mixed> $args array of optional parameters<br/>
 * @param $args array containing the item or fields to show
 * @return string output display string
 */
function dynamicdata_userapi_showdisplay(array $args = [], $context = null)
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
        return xarTpl::module(
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

    $object = DataObjectFactory::getObject($args);
    if (!$object->checkAccess('display')) {
        return xarML('Display #(1) is forbidden', $object->label);
    }
    // set context if available in function
    $object->setContext($context);

    // we're dealing with a real item, so retrieve the property values
    if (!empty($itemid)) {
        $object->getItem();
    }
    // if we are in preview mode, we need to check for any preview values
    //if (!xarVar::fetch('preview', 'isset', $preview,  NULL, xarVar::DONT_SET)) {return;}
    if (!empty($preview)) {
        $object->checkInput();
    }

    return $object->showDisplay($args);
}
