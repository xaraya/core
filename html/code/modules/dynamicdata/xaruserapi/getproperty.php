<?php
/**
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * get a dynamic property
 *
 * @author the DynamicData module development team
 * @param array    $args array of optional parameters<br/>
 *        string   $args['type'] type of property (required)<br/>
 *        string   $args['name'] name for the property (optional)<br/>
 *        string   $args['label'] label for the property (optional)<br/>
 *        string   $args['defaultvalue'] default for the property (optional)<br/>
 *        string   $args['source'] data source for the property (optional)<br/>
 *        string   $args['configuration'] configuration for the property (optional)
 * @return object a particular DataProperty
 */
function &dynamicdata_userapi_getproperty(Array $args=array())
{
    if (empty($args['type'])) {
        $result = null;
        return $result;
    }
    return DataPropertyMaster::getProperty($args);
}

?>
