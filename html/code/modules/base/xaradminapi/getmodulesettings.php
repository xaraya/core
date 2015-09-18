<?php
/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/68.html
 */

/**
 * Get module settings for admin API
 * 
 * @param array $args Optional parameters.
 * @param string $args['module'] Required module parameter
 * @return object Returns data object
 * @throws Exception Thrown if module parameter was not given
 */
function base_adminapi_getmodulesettings(Array $args=array())
{
    if (empty($args['module']))
        throw new Exception(xarML('The getmodulesettings function requires a module parameter'));
    sys::import('modules.dynamicdata.class.objects.master');
    $object = DataObjectMaster::getObject(array('name' => 'module_settings'));

    foreach ($object->properties as $name => $property) {
        $object->properties[$name]->source = 'module variables: ' . $args['module'];
    }
    $object->datastores = array();
    $object->getDatastore();
    
    // Store the module id in the object's field for now
    return $object;
}
?>
