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

    function dynamicdata_object_runmethod(Array $args=array())
    {
        $interface = new SimpleObjectInterface($args);
        return $interface->handle($args);
    }
?>
