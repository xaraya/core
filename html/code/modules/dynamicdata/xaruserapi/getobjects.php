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
 * get the list of defined dynamic objects
 *
 * @author the DynamicData module development team
 * @param array<string, mixed> $args array of optional parameters<br/>
 * @return array<mixed> of object definitions
 */
function dynamicdata_userapi_getobjects(array $args = [], $context = null)
{
    $objects =  DataObjectFactory::getObjects($args);
    return $objects;
}
