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
 * Handle the mail scheduler API
 *
 * @method mixed sendmail(array $args = []) send queued/scheduled mails (executed by the scheduler module)
 * @extends UserApiClass<Module>
 */
class SchedulerApi extends UserApiClass
{
    // ...
}
