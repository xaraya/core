<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * get a dynamic property
 *
 * @author the DynamicData module development team
 * @param $args['type'] type of property (required)
 * @param $args['name'] name for the property (optional)
 * @param $args['label'] label for the property (optional)
 * @param $args['default'] default for the property (optional)
 * @param $args['source'] data source for the property (optional)
 * @param $args['validation'] validation for the property (optional)
 * @returns object
 * @return a particular Dynamic Property
 */
function &dynamicdata_userapi_getproperty($args)
{
    if (empty($args['type'])) {
        return;
    }
    return Dynamic_Property_Master::getProperty($args);
}

?>