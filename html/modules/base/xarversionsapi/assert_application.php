<?php

/**
 * File: $Id$
 *
 * Base User version management functions
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage base
 * @author Jason Judge
 * @todo none
 */


/**
 * Asserts that the Xaraya application version has reached a certain level.
 *
 * @author Jason Judge
 * @param $args['ver'] string version number to compare
 * @returns result of test: true or false
 * @return boolean indicating whether the application is at least version $ver
 */
function base_versionsapi_assert_application($args)
{
    extract($args, EXTR_PREFIX_INVALID, 'p');

    if (!isset($ver)) {
        if (isset($p_0)) {
            $ver = $p_0;
        } else {
            return;
        }
    }

    $result = xarModAPIfunc('base', 'versionsapi', 'compare',
        array(
            'ver1' => $ver,
            'ver2' => xarConfigGetVar('System.Core.VersionNum'),
            'normalize' => 'numeric'
        )
    );

    if ($result < 0) {
        // The supplied version is greater than the system version.
        $msg = xarML('The application version is too low; a later version is required.');
        xarExceptionSet(XAR_USER_EXCEPTION, 'WRONG_VERSION', new SystemException($msg));
        return false;
    }

    return true;
}

?>
