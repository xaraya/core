<?php
/**
 * Base User Version management functions
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 */
/**
 * Order a list of version numbers.
 *
 * @author Jason Judge
 * @param $args['TODO'] TODO
 * @returns result of validation: true or false
 * @return number indicating which parameter is the latest version
 */
function base_versionsapi_order($args)
{
    extract($args);

    // TODO.
    // Sorting would allow different levels to be sorted in a different order
    // sorting could return various formats: flat, tree, etc.

    return true;
}

?>
