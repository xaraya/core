<?php
/**
 * Utility Class to manage Dynamic Properties
 *
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

 /*
     As this file was very long in the 1.x series i've split it up into 3 parts
     in such a way that is not the nicest in coding practices, but makes merges
     if we receive changes from upwards easy to do (i.e. reject all)
     Over time this will probably change. For now i just made sure that everyone
     can still reach the same stuff as before.
 */
sys::import('modules.dynamicdata.class.properties.master');
sys::import('modules.dynamicdata.class.properties.base');
sys::import('modules.dynamicdata.class.properties.registration');

?>
