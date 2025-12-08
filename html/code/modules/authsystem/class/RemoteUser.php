<?php

/**
 * Authsystem Module
 *
 * @package modules\authsystem
 * @category Xaraya Web Applications Framework
 * @version 2.9.3
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/42.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

namespace Xaraya\Authentication;

use Xaraya\Context\Context;
use Xaraya\Context\RequestContext;
use Xaraya\Services\WithServicesTrait;

/**
 * Remote User Authentication
 * where authentication is already done by a reverse proxy in front of Xaraya,
 * and only the remote user is passed to the PHP application
 */
class RemoteUser
{
    use WithServicesTrait;

    public static string $headerName = 'REMOTE_USER';
    public static string $userField = 'id';  // with Role() field values
    public static string $roleField = 'uname';

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
        // static::$roleField = 'email';
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

    public function __construct($xar = null)
    {
        $this->setServicesClass($xar);
    }

    /**
     * Summary of getUserId
     * @param string $uname
     * @return int|null
     */
    public function getUserId($uname)
    {
        $userInfo = $this->getUserInfo($uname);
        if (empty($userInfo) || empty($userInfo[static::$userField])) {
            return null;
        }
        return intval($userInfo[static::$userField]);
    }

    /**
     * Summary of getUserInfo
     * @param string $uname
     * @return array<string, mixed>|null
     */
    public function getUserInfo($uname)
    {
        if (empty($uname)) {
            return null;
        }
        $xar = $this->getServicesClass();
        // Change the role lookup field if needed
        $role = $xar->user()->getRole(static::$roleField, $uname);
        if (empty($role)) {
            return null;
        }
        return $role->getFieldValues();
    }
}
