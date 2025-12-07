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

/**
 * Auth Token Storage
 */
class AuthToken extends CacheStorage
{
    public const ACCESS_LEVELS = ['display', 'update', 'create', 'delete', 'config', 'admin'];
    public static string $headerName = 'HTTP_X_AUTH_TOKEN';
    public static string $cacheType = 'token';
    public static string $fieldName = 'userId';

    /**
     * Summary of init
     * @param array<string, mixed> $config
     * @return void
     */
    public static function init(array $config = [])
    {
        // @todo Change the header name for the auth token if needed
        // RequestContext::$authToken = 'HTTP_X_API_KEY';
    }

    /**
     * Summary of getAuthToken
     * @param Context<string, mixed> $context
     * @return string
     */
    public static function getAuthToken($context): string
    {
        return RequestContext::getAuthToken($context);
    }

    /**
     * Summary of getUserId
     * @param string $token
     * @return int|null
     */
    public function getUserId($token)
    {
        $userInfo = $this->getUserInfo($token);
        if (empty($userInfo) || empty($userInfo[static::$fieldName])) {
            return null;
        }
        return intval($userInfo[static::$fieldName]);
    }
}
