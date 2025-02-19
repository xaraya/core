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
 * Handle the mail cli API
 *
 * @method mixed process(array $args = []) Process a raw email supplied to use by some gateway (ws.php for example) - This function is now simple, but not smart. Ideally we want to do what we - do below very quickly to prevent real-time lock-ups.
 * @extends UserApiClass<Module>
 */
class CliApi extends UserApiClass
{
    public function configure()
    {
        $this->setModType('cli');
        // don't call xarMod:apiLoad() for mail cli API
    }
}
