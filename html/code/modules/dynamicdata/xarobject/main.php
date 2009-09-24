<?php
/**
 * @package modules
 * @copyright (C) 2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 */

/**
 * Use the DataObjectUserInterface() to handle every GUI function for objects (to be handled by the core someday ?)
 *
 * @author mikespub <mikespub@xaraya.com>
 */
sys::import('modules.dynamicdata.class.interface');

function dynamicdata_object_main($args = array())
{
    // we'll use the 'object' GUI function type here, instead of the default 'user' (+ 'admin')
    $args['type'] = 'object';

    $interface = new DataObjectUserInterface($args);

    return $interface->handle($args);
}
?>
