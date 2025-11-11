<?php

/**
 * @package modules\mail
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Mail\UserApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Mail\UserApi;

/**
 * mail userapi getqueuetypes function
 * @extends MethodClass<UserApi>
 */
class GetqueuetypesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     *
     * @param array<string,mixed> $args array of optional parameters<br/>
     * Return a list of queue types in a structured format, also suitable for dd validation
     * @see UserApi::getqueuetypes()
     */
    public function __invoke(array $args = [])
    {
        // We do this here because this way the queue types are translateable
        // and we can easily add qTypes later (like a /dev/null like one) without
        // the definition object changing.
        // This function is called by dd on the validation of the type property
        // of the queues object we are using.
        $qTypes[1] = $this->ml('Incoming mail');
        $qTypes[2] = $this->ml('Outgoing mail');
        $qTypes[3] = $this->ml('Demote  (black hole)');
        $qTypes[4] = $this->ml('Promote (redispatch)');
        return $qTypes;
    }
}
