#!/usr/local/bin/php -q
<?php

/**
 * File: $Id$
 *
 * A server process in php, just proof of concept
 *
 * This file is meant to be called with the CLI version of PHP, mainly
 * because i don't know how to detach from apache with the module version
 * of PHP (if at all possible).
 * 
 * To be able to use it at all the standalone php binary MUST be compiled
 * with --enable-sockets (to use the sockets) 
 *
 * It's mainly meant as an experiment to create nothing but the simplest
 * deamons in PHP. If you really need a daemon use another programming language!
 * 
 * Use:
 * - copy to machine to run it on
 * - configure port to listen to
 * - php -q ./xardaemon.php
 * - use telnet hostname <portnumber> to connect to daemon
 *
 * Code is based on various snippets found on mailinglists and webpages.
 * 
 * @package daemons
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage tests
 * @author Marcel van der Boom <marcel@xaraya.com>
 */

// Do not time out, this is a daemon ;-)
set_time_limit(0);

// defaults...
define('MAXLINE', 1024);        // how much to read from a socket at a time
define('LISTENQ', 10);          // listening queue
define('PORT', 12345);          // the default port to run on
define('MAX_CONNECTIONS', 4);        // file descriptor set size (max number of concurrent clients)...
define('ADDRESS','0.0.0.0');    // all addresses

// set up the file descriptors and sockets...
// $listener only listens for a connection, it doesn't handle anything
// but initial connections, after which the $client array takes over...
$listener = socket_create(AF_INET, SOCK_STREAM, getprotobyname('IP'));
if ($listener) {
    print "Listening on port " . PORT . "\n";
} else {
    die("PANIC -- socket died!\n");
}

// Next taken from php site, undocumented way to reuse address
socket_setopt($listener, SOL_SOCKET, SO_REUSEADDR, 1);
if (!socket_bind($listener, ADDRESS, PORT)) {
    socket_close($listener);
    die("PANIC -- Couldn't bind!\n");
}
socket_listen($listener, LISTENQ);


// set up our clients. After listener receives a connection,
// the connection is handed off to a $client[]. 
// Set up one extra, so we can issue a msg when nr of clients reached maximum
for ($i = 0; $i <= MAX_CONNECTIONS; $i++) $client[$i] = null;


/**
 * the main loop.
 *
 */
while(1) {
    // Obviously we want the listener in the watch array
    $active_sockets = array();
    $active_sockets[0] = $listener;
    // Build the array of read sockets to watch
    for ($i = 0; $i <= MAX_CONNECTIONS; $i++) {
        // Add client to watched array if it has connected before
        if ($client[$i] != null) $active_sockets[$i + 1] = $client[$i];
    }

    // Check whether we are over the limit
    if (count($active_sockets) > MAX_CONNECTIONS + 1) {
        // The accepted connection is actually one to many (on purpose) issue msg and disconnect
        // Find the highest resource, which is the latest client and disconnect it
        $offender = array_search((int)max($client),$client);
        socket_write($client[$offender],"Maximum number of connections reached, sorry. Disconnecting...\n");
        closeClient($offender);
        continue;
    }

    // Make a watch for the clients which are active (and the listener)
    $nready = socket_select($active_sockets, $null, $null, null);

    // if we have a new connection, stick it in the $client array
    if (in_array($listener, $active_sockets)) {
        print "Listener heard something, setting up new client\n";
        
        for ($i = 0; $i <= MAX_CONNECTIONS; $i++) {
            if ($client[$i] == null) {
                // accept the connection 
                $client[$i] = socket_accept($listener);
                socket_setopt($client[$i], SOL_SOCKET, SO_REUSEADDR, 1);
                socket_getpeername($client[$i], $remote_host[$i], $remote_port[$i]);
                print "Accepted {$remote_host[$i]}:{$remote_port[$i]} as client[$i]\n";
                socket_write($client[$i],"Welcome to xar php daemon play\n");
                socket_write($client[$i],"/quit quits, anything else will be echoed\n");
                // Quit the for loop
                break;
            }
        }
        // More changed status? If not, reloop
        if (--$nready <= 0)  continue;
    }

    /**
     * The code below is what should be customized if this daemon is
     * to be used for something else. What it does now is just echo
     * the input to the user and all other clients, thus mimicking a
     * very crippled IRC server.
     * 
     * For xaraya we obviously need something more specialized
     *
     * Some ideas:
     * - internal scheduler (if cron is no alternative)
     * - xmlrpc server for xaraya specifically (or webservices in general)
     * - server persistent part (core or data structures)
     * - object broker service (allow remote execution of objects)
     * - operation queue to escape round-trip delays (long operation runs in the daemon, signalling user somehow)
     * - email gateway
     * - scripting interface / cmd terminal for xaraya (set configvar='value', show users status=active), scriptable of course
     * - event monitoring (do this when that happens)
     * - glue between xaraya and other applications
     * - syndication engine
     * - well, enough for now
     */
 
    // check the clients for incoming data.
    for ($i = 0; $i <= MAX_CONNECTIONS; $i++) {
        if ($client[$i] == null) continue;
        
        // Did the client socket change state, i.e. is there input?
        if (in_array($client[$i], $active_sockets)) {
            $input = trim(socket_read($client[$i], MAXLINE));
            
            // Empty input closes the client
            if (!$input) 
                closeClient($i);  
            else {
                switch($input) {
                case '/killme':
                    killDaemon();
                    break;
                case '/quit':
                    closeClient($i);
                    break;
                default:
                    // print something on the server, then echo the incoming
                    // data to all of the clients in the $client array.
                    print "From {$remote_host[$i]}:{$remote_port[$i]}, client[$i]: $input\n";
                    for ($j = 0; $j <= MAX_CONNECTIONS; $j++) {
                        if ($client[$j] != null) socket_write($client[$j], "From client[$i]: $input\r\n");
                    }
                }
            }
            // Break out of for loop when there are no other sockets which changed state
            if  (--$nready <= 0) break;
        }
    }
}


function killDaemon()
{
    global $listener, $client;
    
    socket_close($listener);
    $msg = "Daemon going down!\n";
    for ($i = 0; $i <= MAX_CONNECTIONS; $i++) {
        if ($client[$i] != null) {
            socket_write($client[$i], $msg, strlen($msg));
            socket_close($client[$i]);
        }
    }
    print "Shutting down the daemon\n";
    exit;
}


function closeClient($i)
{
    global $client, $remote_host, $remote_port;
    
    print "closing client[$i] ({$remote_host[$i]}:{$remote_port[$i]})\n";
    
    socket_close($client[$i]);
    $client[$i] = null;
    unset($remote_host[$i]);
    unset($remote_port[$i]);
}

?>