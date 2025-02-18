<?php

/**
 * @package modules\mail
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Mail;

use Xaraya\Modules\UserApiClass;
use sys;

sys::import('xaraya.modules.userapi');

/**
 * Handle the mail user API
 *
 * @method mixed getitemtypes(array $args = [])
 * @method mixed getqueues(array $args = [])
 * @method mixed getqueuetypes(array $args = [])
 * @method mixed qisactive(array $args = [])
 * @extends UserApiClass<Module>
 */
class UserApi extends UserApiClass
{
    // ...
}
