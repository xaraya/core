<?php

/**
 * @package core\bridge
 * @subpackage restapi
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 *
 * @author mikespub <mikespub@xaraya.com>
 */

namespace Xaraya\Bridge\RestAPI;

use Xaraya\Authentication\AuthToken;
use xarMod;
use xarModVars;
use xarRoles;
use xarUser;
use sys;
use UnauthorizedOperationException;

sys::import('xaraya.context.context');
sys::import('modules.authsystem.class.authtoken');

/**
 * Class to handle Generic REST API calls
 */
class GenericAPIHandler extends RestAPIHandler
{
    /**
     * Return the current user or exit with 401 status code
     * @param array<string, mixed> $args
     * @uses xarMod::init()
     * @uses xarUser::init()
     * @return array<string, mixed>
     */
    public function whoami($args = [])
    {
        $userId = $this->checkUser();
        //return array('id' => xarUser::getVar('id'), 'name' => xarUser::getVar('name'));
        xarMod::init();
        xarUser::init();
        $role = xarRoles::getRole($userId);
        $user = $role->getFieldValues();
        $context = $this->getContext();
        if (isset($context['authMethod'])) {
            return ['id' => $user['id'], 'name' => $user['name'], 'source' => $context['authMethod']];
        }
        return ['id' => $user['id'], 'name' => $user['name']];
    }

    /**
     * Return the current context or exit with 401 status code
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    public function showContext($args = [])
    {
        $context = $this->getContext();
        $userId = $context->getUserId();
        // return restricted version for non-site admin
        if (empty($userId) || $userId != xarModVars::get('roles', 'admin')) {
            return ['userId' => $userId, 'error' => 'Restricted to site admin'];
        }
        return $context->getArrayCopy();
    }

    /**
     * Summary of postToken
     * @param array<string, mixed> $args
     * @uses xarMod::init()
     * @uses xarUser::init()
     * @uses xarMod::apiFunc()
     * @throws \UnauthorizedOperationException
     * @return array<string, mixed>
     */
    public function postToken($args)
    {
        // this contains any POSTed args from rst.php
        if (empty($args['input'])) {
            $args['input'] = [];
        }
        $uname = $args['input']['uname'];
        $pass = $args['input']['pass'];
        if (empty($uname) || empty($pass)) {
            if (!headers_sent()) {
                //header('WWW-Authenticate: Bearer realm="Xaraya Site Login", access=');
                header('WWW-Authenticate: Token realm="Xaraya Site Login", uname=, pass=');
            }
            throw new UnauthorizedOperationException();
        }
        $access = $args['input']['access'];
        if (empty($access) || !in_array($access, AuthToken::ACCESS_LEVELS)) {
            if (!headers_sent()) {
                //header('WWW-Authenticate: Bearer realm="Xaraya Site Login", access=');
                header('WWW-Authenticate: Token realm="Xaraya Site Login", access=');
            }
            throw new UnauthorizedOperationException();
        }
        $context = $this->getContext();
        //xarSession::init();
        xarMod::init();
        xarUser::init();
        // @checkme unset xarSession role_id if needed, otherwise xarUser::logIn will hit xarUser::isLoggedIn first!?
        // @checkme or call authsystem directly if we don't want/need to support any other authentication modules
        $userId = xarMod::apiFunc('authsystem', 'user', 'authenticate_user', $args['input'], $context);
        if (empty($userId) || $userId == xarUser::AUTH_FAILED) {
            if (!headers_sent()) {
                //header('WWW-Authenticate: Bearer realm="Xaraya Site Login"');
                header('WWW-Authenticate: Token realm="Xaraya Site Login"');
            }
            throw new UnauthorizedOperationException();
        }
        $userInfo = ['userId' => $userId, 'access' => $access, 'created' => time()];
        $token = AuthToken::createToken($userInfo);
        $expiration = date('c', time() + AuthToken::$tokenExpires);
        return ['access_token' => $token, 'expiration' => $expiration, 'role_id' => $userId];
    }

    /**
     * Summary of deleteToken
     * @param array<string, mixed> $args
     * @return bool
     */
    public function deleteToken($args = [])
    {
        // verify that the cookie corresponds to an authorized user (with minimal core load) or exit - see whoami
        $userId = $this->checkUser();
        $context = $this->getContext();
        // check if we had an auth token before
        $token = AuthToken::getAuthToken($context);
        if (empty($token)) {
            return false;
        }
        AuthToken::deleteToken($token);
        return true;
    }
}
