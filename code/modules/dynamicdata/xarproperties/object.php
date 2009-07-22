<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Include the base class
 */
sys::import('modules.dynamicdata.xarproperties.objectref');

/**
 * Handle the object property
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
