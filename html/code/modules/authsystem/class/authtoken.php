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
use ixarCache_Storage;
use xarCache;

/**
 * Auth Token Storage
 */
class AuthToken
{
    public const ACCESS_LEVELS = ['display', 'update', 'create', 'delete', 'config', 'admin'];
    public static int $tokenExpires = 12 * 60 * 60;  // 12 hours
    public static string $storageType = 'apcu';  // database or apcu
    public static ?ixarCache_Storage $tokenStorage = null;
    public static string $headerName = 'HTTP_X_AUTH_TOKEN';

    /**
     * Summary of init
     * @param array<string, mixed> $config
     * @return void
     */
    public static function init(array $config = [])
    {
        // @todo Change the header name for the auth token if needed
        // RequestContext::$authToken = 'HTTP_X_API_KEY';
        /**
        try {
            RequestContext::$authToken = xarSystemVars::get(sys::CONFIG, 'Auth.AuthToken');
        } catch (Exception) {
            return;
        }
         */
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
     * Summary of getUserInfo
     * @param string $token
     * @return array<string, mixed>|null
     */
    public static function getUserInfo($token)
    {
        if (empty($token) || !(self::getTokenStorage()->isCached($token))) {
            return null;
        }
        $userInfo = self::getTokenStorage()->getCached($token);
        if (empty($userInfo)) {
            return null;
        }
        $userInfo = json_decode($userInfo, true, 512, JSON_THROW_ON_ERROR);
        if (!empty($userInfo['userId']) && ($userInfo['created'] > (time() - self::$tokenExpires))) {
            return $userInfo;
        }
        return null;
    }

    /**
     * Summary of createToken
     * @param array<string, mixed> $userInfo
     * @return string|null
     */
    public static function createToken($userInfo)
    {
        if (function_exists('random_bytes')) {
            $token = bin2hex(random_bytes(32));
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $token = bin2hex(openssl_random_pseudo_bytes(32));
        } else {
            return null;
        }
        $userInfo['created'] = time();
        $userInfo['updated'] = $userInfo['created'];
        // @checkme clean up cachestorage occasionally based on size
        self::getTokenStorage()->sizeLimitReached();
        self::getTokenStorage()->setCached($token, json_encode($userInfo));
        return $token;
    }

    /**
     * Summary of updateToken
     * @param string $token
     * @param array<string, mixed> $userInfo
     * @return void
     */
    public static function updateToken($token, $userInfo)
    {
        $userInfo['updated'] = time();
        self::getTokenStorage()->setCached($token, json_encode($userInfo));
    }

    /**
     * Summary of deleteToken
     * @param string $token
     * @return void
     */
    public static function deleteToken($token)
    {
        if (empty($token) || !(self::getTokenStorage()->isCached($token))) {
            return;
        }
        self::getTokenStorage()->delCached($token);
    }

    /**
     * Summary of getTokenStorage
     * @uses xarCache::getStorage()
     * @return ixarCache_Storage
     */
    public static function getTokenStorage()
    {
        if (!isset(self::$tokenStorage)) {
            //self::loadConfig();
            // @checkme access cachestorage directly here
            self::$tokenStorage = xarCache::getStorage([
                'storage' => self::$storageType,
                'type' => 'token',
                'expire' => self::$tokenExpires,
                'sizelimit' => 2000000,
            ]);
        }
        return self::$tokenStorage;
    }
}
