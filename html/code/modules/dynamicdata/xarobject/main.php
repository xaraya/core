<?php
/**
 * Main entry point for the object interface of this module
 *
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.html
 *
 */

/**
 * Use the DataObjectUserInterface() to handle every GUI function for objects (deprecated - see xaraya.objects in core)
 * @return mixed output display string or boolean true if redirected
 *
 * @author mikespub <mikespub@xaraya.com>
 */
sys::import('modules.dynamicdata.class.userinterface');

function dynamicdata_object_main($args = array())
{
    // we'll use the 'object' GUI link type here, instead of the default 'user' (+ 'admin')
    $args['linktype'] = 'object';

    $interface = new DataObjectUserInterface($args);

    return $interface->handle($args);
}
?>