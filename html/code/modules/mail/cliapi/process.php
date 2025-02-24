<?php

/**
 * @package modules\mail
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Mail\CliApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Mail\CliApi;
use Xaraya\Modules\Mail\AdminApi;
use xarLog;
use xarMailParser;
use xarMod;
use xarModHooks;
use xarUser;
use sys;

sys::import('xaraya.modules.method');

/**
 * mail cliapi process function
 * @extends MethodClass<CliApi>
 */
class ProcessMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Process a raw email supplied to use by some gateway (ws.php for example)
     * This function is now simple, but not smart. Ideally we want to do what we
     * do below very quickly to prevent real-time lock-ups.
     * In other words, the current code assumes we dont get many mails :-)
     * @return int exitcode to gateway script
     * @todo what do we do with security here?
     * @see CliApi::process()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        $this->log()->info("MAIL: processing incoming message");
        extract($args);
        assert($argc > 0 && $argv[1] == "mail");

        // TODO: Guess ;-)
        if (isset($argv[2]) && $argv[2] == '-u') {
            $user = $argv[3];
        }
        if (isset($argv[4]) && $argv[4] == '-p') {
            $pass = $argv[5];
        }
        if (!isset($user) or !isset($pass)) {
            echo "Usage: mail -u <user> -p <pass> [mailcontent]\n";
            return 1;
        }
        if (!xarUser::logIn($user, $pass)) {
            echo "Authentication failed\n";
            return 1;
        }
        // 1. Read stdin for the mail contents (raw)
        // TODO: what to do when there is silence?
        $input = file_get_contents('php://stdin');
        if (!isset($input)) {
            return $this->fatal("Could not read from php://stdin");
        }
        if (strlen($input) == 0) {
            return 0;
        } // ok, but nothing to do here

        // 2. Parse the input, we do this early so it never enters the system when it cannot be parsed.
        sys::import('modules.mail.class.decode');
        $parser = new xarMailParser($input);
        $structure = $parser->decode();
        if ($parser->isError($structure)) {
            return $this->fatal("Could not parse input");
        }

        // 3. Based on parse results determine the queue
        // This would typically be something we want to postpone, that is, put it in a default queue quickly
        // and revisit this later on.
        $destination = $adminapi->maptoqueue(['msg_structure' => $structure]);
        if (!isset($destination)) {
            return $this->fatal("Could not map input to a queue.");
        }

        // 4. Put the message ($raw) into the queues
        // This would typically be something we want to postpone.

        foreach ($destination as $q) {
            $result = $q->push($input);
        }

        // 5. Generate create hook calls?
        // This would typically be something we want to postpone.
        // TODO: insert xarModHooks::call blah blah here.

        // Once we got here, stuff is ok
        return 0;
    }

    public function fatal($msg)
    {
        fwrite(STDERR, 'ERROR: ' . $msg . "\n");
        return 1;
    }
}
