<?php
/**
 * @package modules
 * @subpackage base module
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @link http://xaraya.com/index.php/release/68.html
 */

function base_adminapi_getmodulesettings($args)
{
    if (empty($args['module']))
        throw new Exception(xarML('The getmodulesettings function requires a module parameter'));
    sys::import('modules.dynamicdata.class.objects.master');
    $object = DataObjectMaster::getObject(array('name' => 'module_settings'));

    foreach ($object->properties as $name => $property) {
        $object->properties[$name]->source = 'module variables: ' . $args['module'];
    }
    $object->datastores = array();
    $object->getDatastores();
    return $object;
}
?>