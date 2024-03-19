<?php
/**
 * Authsystem Module
 *
 * @package modules\authsystem
 * @category Xaraya Web Applications Framework
 * @version 2.4.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/42.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

namespace Xaraya\Authentication;

use Xaraya\Context\Context;
use Xaraya\Context\RequestContext;
use xarRoles;

/**
 * Remote User Authentication
 * where authentication is already done by a reverse proxy in front of Xaraya,
 * and only the remote user is passed to the PHP application
 */
class RemoteUser
{
    public static string $headerName = 'REMOTE_USER';
    public static string $lookupField = 'uname';

    /**
     * Summary of init
     * @param array<string, mixed> $config
     * @return void
     */
    public static function init(array $config = [])
    {
        // @todo Change the header name for the remote user if needed
        // RequestContext::$remoteUser = 'HTTP_X_WEBAUTH_USER';
        // Change the role lookup field if needed
        // static::$lookupField = 'email';
        /**
        try {
            RequestContext::$remoteUser = xarSystemVars::get(sys::CONFIG, 'Auth.RemoteUser');
        } catch (Exception) {
            return;
        }
         */
    }

    /**
     * Summary of getRemoteUser
     * @param Context<string, mixed> $context
     * @return string
     */
    public static function getRemoteUser($context): string
    {
        return RequestContext::getRemoteUser($context);
    }

    /**
     * Summary of getUserInfo
     * @param string $uname
     * @return array<string, mixed>|null
     */
    public static function getUserInfo($uname)
    {
        if (empty($uname)) {
            return null;
        }
        // Change the role lookup field if needed
        $role = xarRoles::ufindRole($uname);
        if (empty($role)) {
            return null;
        }
        return $role->getFieldValues();
    }
}
