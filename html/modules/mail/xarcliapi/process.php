<?php
/**
 * Process a raw email supplied to use by some gateway (ws.php for example)
 *
 * This function is now simple, but not smart. Ideally we want to do what we
 * do below very quickly to prevent real-time lock-ups. 
 * In other words, the current code assumes we dont get many mails :-)
 *
 * @return int exitcode to gateway script
 * @todo what do we do with security here?
 */
function mail_cliapi_process($args)
{
    xarLogMessage("MAIL: processing incoming message");
    extract($args);
    assert('$argc > 0 && $argv[1] == "mail"; /* Wrong call to mail_cli_process handler */');

    // TODO: Guess ;-)
    if(isset($argv[2]) && $argv[2]=='-u') $user = $argv[3];
    if(isset($argv[4]) && $argv[4]=='-p') $pass = $argv[5];
    if(!isset($user) or !isset($pass)) 
    {
        echo "Usage: mail -u <user> -p<pass> [mailcontent]\n";
        return 1;
    }
    if(!xarUserLogin($user,$pass)) {
        echo "Authentication failed\n";
        return 1;
    }
    // 1. Read stdin for the mail contents (raw)
    // TODO: what to do when there is silence?
    $input = file_get_contents('php://stdin');
    if(!isset($input)) return _fatal("Could not read from php://stdin");
    if(strlen($input) == 0) return 0; // ok, but nothing to do here

    // 2. Parse the input, we do this early so it never enters the system when it cannot be parsed.
    sys::import('modules.mail.xarclass.decode');
    $parser = new xarMailParser($input);
    $structure = $parser->decode();
    if($parser->isError($structure)) return _fatal("Could not parse input");

    // 3. Based on parse results determine the queue
    // This would typically be something we want to postpone, that is, put it in a default queue quickly
    // and revisit this later on.
    $destination = xarModApiFunc('mail','admin','maptoqueue',array('msg_structure' => $structure));
    if(!isset($destination)) return _fatal("Could not map input to a queue.");

    // 4. Put the message ($raw) into the queues
    // This would typically be something we want to postpone.
   
    foreach ($destination as $q) {
        $result = $q->push($input);
    }

    // 5. Generate create hook calls?
    // This would typically be something we want to postpone.
    // TODO: insert xarModCallHooks blah blah here.

    // Once we got here, stuff is ok
    return 0;
}

function _fatal($msg)
{
    fwrite(STDERR,'ERROR: '. $msg."\n");
    return 1;
}
?>
