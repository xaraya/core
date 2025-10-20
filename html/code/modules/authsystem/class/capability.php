<?php

/**
 * Authsystem Module
 *
 * @package modules\authsystem
 * @category Xaraya Web Applications Framework
 * @version 2.8.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/42.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

namespace Xaraya\Authentication;

/**
 * Authentication modules capabilities - moved from lib/xaraya/users.php
 * (to be revised e.g. to differentiate read & update capability for core & dynamic)
 * @deprecated 2.8.1 not used in 2.4+ auth modules
 * define('XARUSER_AUTH_AUTHENTICATION', 1);
 * ...
 */
class Capability
{
    public const AUTHENTICATION = 1;
    public const DYNAMIC_USER_DATA_HANDLER = 2;
    public const PERMISSIONS_OVERRIDER = 16;
    public const USER_CREATEABLE = 32;
    public const USER_DELETEABLE = 64;
    public const USER_ENUMERABLE = 128;
}
