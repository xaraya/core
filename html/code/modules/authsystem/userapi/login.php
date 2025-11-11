<?php

/**
 * @package modules\authsystem
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Authsystem\UserApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Authsystem\UserApi;
use BadParameterException;

/**
 * authsystem userapi login function
 * @extends MethodClass<UserApi>
 */
class LoginMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Api function to log a user on to the system
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param string[] $args Array of optional parameters<br/>
     * string    $args['uname'] User name of user<br/>
     * string    $args['pass'] Password of user<br/>
     * string    $args['rememberme'] Remember this user (optional)
     * @return bool Returns true on success, false upon failure
     * @see UserApi::login()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($rememberme)) {
            $rememberme = 0;
        }

        if ((!isset($uname))
            || (!isset($pass))) {
            throw new BadParameterException(null, $this->ml('Wrong arguments to authsystem_userapi_login.'));
        }

        return $this->user()->logIn($uname, $pass, $rememberme);
    }
}
