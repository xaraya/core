<?php
/**
 * Process a raw email supplied to use by some gateway (ws.php for example)
 *
 * This function is now simple, but not smart. Ideally we want to do what we
 * do below very quickly. In other words, we assume we dont get many mails :-)
 *
 * @returns int exitcode to gateway script
 * @todo what do we do with security here?
 */
function mail_cliapi_process($args)
{
    extract($args);
    assert('$argc > 0 && $argv[1] == "mail"; /* Wrong call to mail_cli_process handler */');

    // 1. Read stdin for the mail contents (raw)
    $input = file_get_contents('php://stdin');
    if(!isset($input)) return _fatal("Could not read from php://stdin");
    if(strlen($input) == 0) return 0; // ok, but nothing to do here

    // 2. Parse the input, we do this early so it never enters the system when it cannot be parsed.
    include_once "modules/mail/xarclass/class.decode.php";
    $parser = new xarMailParser($input);
    $structure = $parser->decode();
    if($parser->isError($structure)) return _fatal("Could not parse input");

    // 3. Based on parse results determine the queue
    // This would typically be something we want to postpone.
    $queue = xarModApiFunc('mail','admin','maptoqueue',array('msg_structure' => $structure));
    if(!isset($queue)) return _fatal("Could not map input to a queue.");

    // 4. Put the message ($raw) into the queue
    // This would typically be something we want to postpone.
    //$result = $queue->push($input);
    //if(!$result) return _fatal("Could not push intput into determined queue");

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
