<?php
/**
 * @package modules
 * @copyright (C) 2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 */

/**
 * Display the results of an object list method directly
 *
 * @author Marc Lutolf <mfl@netspan.ch>
 */
sys::import('modules.dynamicdata.class.simpleinterface');
function dynamicdata_object_runmethod($args)
{
    $interface = new Simple_Object_Interface($args);
    return $interface->handle($args);
}
?>
