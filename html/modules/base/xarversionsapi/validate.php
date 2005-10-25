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
 * Validate the format of a version number against some rule.
 *
 * @author Jason Judge
 * @param $args['ver'] string version number to validate
 * @param $args['rule'] string rule name to validate against
 * @returns result of validation: true or false
 * @return boolean indicating whether the rule was passed (NULL for parameter error)
 */
function base_versionsapi_validate($args)
{
    extract($args);

    // Rules could include:
    // - numeric only
    // - strict number of levels
    // - implied '0' on empty levels allowed

    if (!isset($ver) || !isset($rule)) {
        return;
    }

    // Set of rules. These can be extended as needed.
    $regex = array();

    // [n].n[.n ...]
    $regex['application'] = '/^\d*\.\d+(\.\d+)*$/';
    // n[.n ...]
    $regex['module'] = '/^\d+(\.\d+)*$/';

    if (isset($regex[$rule])) {
        if (preg_match($regex[$rule], $ver)) {
            return true;
        } else {
          return false;
        }
    }

    return;
}

?>
