<?php

/**
 * @package modules\modules
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Authsystem;

use Xaraya\Modules\UserApiClass;
use sys;

sys::import('xaraya.modules.userapi');

/**
 * Handle the modules rest API
 *
 * @method mixed getlist(array $args = []) Get the list of REST API calls supported by this module (if any)
 * @method mixed honeypot(array $args = []) Sample REST API call supported by this module (if any)
 * @extends UserApiClass<Module>
 */
class RestApi extends UserApiClass
{
    // ...
}
