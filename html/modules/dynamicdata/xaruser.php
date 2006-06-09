<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 */
/**
 * Dynamic Data User function
 * @author mikespub <mikespub@xaraya.com>
 */

require_once 'modules/dynamicdata/class/objects.php';

// ----------------------------------------------------------------------
// Hook functions (user GUI)
// ----------------------------------------------------------------------


//  Ideally, people should be able to use the dynamic fields in their
//  module templates as if they were 'normal' fields -> this means
//  adapting the get() function in the user API of the module, and/or
//  using some common data retrieval function (DD) in the future...

/*  display hook is now disabled by default - use the BL tags or APIs instead */

?>
