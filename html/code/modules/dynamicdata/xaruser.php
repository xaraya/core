<?php
/**
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.html
 */
/**
 * Dynamic Data User function
 * @author mikespub <mikespub@xaraya.com>
 */

sys::import('modules.dynamicdata.class.objects');

// ----------------------------------------------------------------------
// Hook functions (user GUI)
// ----------------------------------------------------------------------


//  Ideally, people should be able to use the dynamic fields in their
//  module templates as if they were 'normal' fields -> this means
//  adapting the get() function in the user API of the module, and/or
//  using some common data retrieval function (DD) in the future...

/*  display hook is now disabled by default - use the BL tags or APIs instead */

?>
