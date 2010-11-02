<?php
/**
 * @package modules
 * @subpackage dynamicdata module
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 */

/**
 * Use the DataObjectUserInterface() to handle every GUI function for objects (deprecated - see xaraya.objects in core)
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
