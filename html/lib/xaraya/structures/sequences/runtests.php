#!/usr/bin/php5
<?php

/**
 * @package core\structures
 * @subpackage structures
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

/* Uncomment to run
// Save the directory where we are now
$savedir = getcwd();
chdir('/var/mt/xar/core/core.2.x/html');

include_once('bootstrap.php');
sys::import('xaraya.core');

xarCore::xarInit(xarCore::SYSTEM_ALL);

if(!xar::user()->logIn('Admin','12345')) {
    throw new Exception("Authentication failed\n");
} else {
    xar_m('Authenticated');
}


sys::import('xaraya.structures.sequences.queue');
sys::import('xaraya.structures.sequences.stack');
xar_m('WHY IS THIS NOT USING THE LOVELY UNITTESTS?');
$l=0;
xar_m('Testing DD queue',$l++);
$q = new Queue('dd',array('name'=>'masterq'));
$q->clear();
xar_tests($q,$l--);

xar_m('Testing DD stack',$l++);
$q = new Stack('dd',array('name'=>'masterq'));
$q->clear();
xar_tests($q,$l--);

xar_m('Testing array queue',$l++);
$q = new Queue();
$q->clear();
xar_tests($q,$l--);

xar_m('Testing array stack',$l++);
$q = new Stack();
$q->clear();
xar_tests($q,$l--);
*/

function xar_m($msg, $level = 0)
{
    $prefix = str_repeat('  ', $level);
    echo "$prefix - $msg\n";
}

function xar_tests($seq, $l = 0)
{
    $seqName = get_class($seq);
    xar_m("Operations on empty $seqName", $l++);
    xar_m("Size of empty $seqName: " . $seq->size, $l);
    $s = $seq->empty ? "yes" : "NO?";
    xar_m("Empty $seqName is empty: $s", $l);
    xar_m("Popping from empty $seqName", $l);
    $seq->pop();
    $l--;

    $seq->clear();
    xar_m("Pushing and popping 1 item into the $seqName", $l++);
    xar_m("first", $l);
    $seq->push("first", $l--);
    xar_m("Getting items back", $l++);
    xar_m($seq->pop(), $l);
    $l--;

    xar_m("Pushing and popping 3 items into the $seqName", $l++);
    xar_m("first", $l);
    $seq->push("first");
    xar_m("second", $l);
    $seq->push("second");
    xar_m("third", $l);
    $seq->push("third");
    $l--;

    xar_m("Getting items back", $l++);
    xar_m($seq->pop(), $l);
    xar_m($seq->pop(), $l);
    xar_m($seq->pop(),$l);
    $l--;
}
