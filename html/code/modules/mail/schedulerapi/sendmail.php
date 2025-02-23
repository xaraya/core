<?php

/**
 * @package modules\mail
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Mail\SchedulerApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Mail\SchedulerApi;
use Xaraya\Modules\Mail\AdminApi;
use xarMod;
use xarModVars;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * mail schedulerapi sendmail function
 * @extends MethodClass<SchedulerApi>
 */
class SendmailMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * send queued/scheduled mails (executed by the scheduler module)
     * @author mikespub
     * @access public
     * @see SchedulerApi::sendmail()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        $log = $this->ml('Starting to send queued mail') . "\n";

        // TODO: use separate xar_mail_queue table here someday
        // get the waiting queue
        $serialqueue = xarModVars::get('mail', 'queue');
        if (!empty($serialqueue)) {
            $queue = unserialize($serialqueue);
        } else {
            $queue = [];
        }
        $now = time();
        $sent = [];
        foreach ($queue as $id => $when) {
            // see if we need to send this mail already or not
            if ($when > $now) {
                continue;
            }

            $log .= $this->ml('Sending mail #(1)', $id) . ' ';
            // retrieve the mail data
            $data = xarModVars::get('mail', $id);
            if (empty($data)) {
                $log .= $this->ml('empty') . "\n";
                $sent[] = $id;
                continue;
            }
            $args = unserialize($data);
            unset($args['when']);
            // send it with the internal _sendmail API function
            if ($adminapi->internal_sendmail($args)) {
                $log .= $this->ml('succeeded');
                xarModVars::delete('mail', $id);
                $sent[] = $id;
            } else {
                $log .= $this->ml('failed');
                // CHECKME: do we try again later or not ? That should probably depend on the error ;)
                xarModVars::delete('mail', $id);
                $sent[] = $id;
            }
            $log .= "\n";
        }
        $log .= $this->ml('Finished sending queued mail');

        // we didn't send anything, so return now
        if (count($sent) == 0) {
            return $log;
        }

        // Trick : make sure we're dealing with up-to-date information here,
        //         because sending all those mails may have taken a while...
        $this->var()->delCached('Mod.Variables.mail', 'queue');

        // get the current waiting queue
        $serialqueue = xarModVars::get('mail', 'queue');
        if (!empty($serialqueue)) {
            $queue = unserialize($serialqueue);
        } else {
            $queue = [];
        }
        // remove the sent mails from the queue
        foreach ($sent as $id) {
            if (isset($queue[$id])) {
                unset($queue[$id]);
            }
        }
        // update the waiting queue
        $serialqueue = serialize($queue);
        xarModVars::set('mail', 'queue', $serialqueue);

        return $log;
    }
}
