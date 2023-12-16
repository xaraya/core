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
 */

/**
 * Display the results of an object list method directly
 *
 * @author Marc Lutolf <mfl@netspan.ch>
 */
sys::import('modules.dynamicdata.class.simpleinterface');

function dynamicdata_object_runmethod(array $args = [], $context = null)
{
    $interface = new SimpleObjectInterface($args);
    // set context if available in function
    $interface->setContext($context);

    return $interface->handle($args);
}
