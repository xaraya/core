<?php
/**
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * get a dynamic property
 *
 * @author the DynamicData module development team
 * @param $args['type'] type of property (required)
 * @param $args['name'] name for the property (optional)
 * @param $args['label'] label for the property (optional)
 * @param $args['defaultvalue'] default for the property (optional)
 * @param $args['source'] data source for the property (optional)
 * @param $args['configuration'] configuration for the property (optional)
 * @return object a particular DataProperty
 */
function &dynamicdata_userapi_getproperty($args)
{
    if (empty($args['type'])) {
        $result = null;
        return $result;
    }
    return DataPropertyMaster::getProperty($args);
}

?>
