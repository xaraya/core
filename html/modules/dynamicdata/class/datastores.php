<?php
/**
 * Utility Class to manage Dynamic Data Stores
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */


sys::import('xaraya.datastores.interface');

/*
    As this file was long in the 1.x series i've split it up into 2 parts
    in such a way that is not the nicest in coding practices, but makes merges
    if we receive changes from upwards easy to do (i.e. reject all)
    Over time this will probably change. For now i just made sure that everyone
    can still reach the same stuff as before.
*/
sys::import('modules.dynamicdata.class.datastores.master');
sys::import('modules.dynamicdata.class.datastores.base');


?>
