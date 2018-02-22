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
 * Include the base class
 */
sys::import('modules.dynamicdata.xarproperties.objectref');

/**
 * * This is a specific version of the objectref property
 * It displays a drodown of defined dataobjects
 * All dataobjects are defined to be items of the "first" dataobject (called "object"), 
 * so this is nothing more than an objectref property where the object in question is "object"
 *
 * Options available to user selection
 * ===================================
 * Options take the form:
 *   option-type:option-value;
 * option-types:
 *   static:true - add modules to the list
 */
class ObjectProperty extends ObjectRefProperty
{
    public $id         = 24;
    public $name       = 'object';
    public $desc       = 'Object';

    public $initialization_store_prop   = 'objectid';       // Name of the property we want to use for storage
}

?>