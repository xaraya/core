#!/usr/bin/php5
<?php

// Save the directory where we are now
$savedir = getcwd();
chdir('/var/mt/xar/core/mail-in/html');
include 'includes/xarCore.php';
// TODO: don't load the whole core
xarCoreInit(XARCORE_SYSTEM_ALL);

function m($msg) { echo "$msg\n"; }

if(!xarUserLogin('Admin','12345')) {
    die("Authentication failed\n");
} else {
    m('Authenticated');
}


include_once('includes/structures/sequences/queue.php');
include_once('includes/structures/sequences/stack.php');

m('Creating new dd queue');
$q = new Queue('dd',array('name'=>'masterq'));
$q->clear();

m('Operations on empty Q');
m("Size of empty Q: ".$q->size);
$s=$q->empty?"yes":"NO?";
m("Empty Q is empty: $s");
m('Popping from empty Q');
$q->pop();

$q->clear();
m('Pushing and popping 1 item into the Q');
m('first');
$q->push('first');
m('Getting items back');
m($q->pop());

m('Pushing and popping 3 items into the Q');
m('first');
$q->push('first');
m('second');
$q->push('second');
m('third');
$q->push('third');

m('Getting items back');
m($q->pop());
m($q->pop());
m($q->pop());

$q = new Stack('dd',array('name'=>'masterq'));
m('Operations on empty S');
m("Size of empty S: ".$q->size);
$s=$q->empty?"yes":"NO?";
m("Empty S is empty: $s");
m('Popping from empty S');
$q->pop();

$q->clear();
m('Pushing and popping 1 item into the S');
m('first');
$q->push('first');
m('Getting items back');
m($q->pop());

m('Pushing and popping 3 items into the S');
m('first');
$q->push('first');
m('second');
$q->push('second');
m('third');
$q->push('third');

m('Getting items back');
m($q->pop());
m($q->pop());
m($q->pop());


?>