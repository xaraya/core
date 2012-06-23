#!/usr/bin/php5
<?php
/**
 * @package core
 * @subpackage structures
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 */

/* Uncomment to run
// Save the directory where we are now
$savedir = getcwd();
chdir('/var/mt/xar/core/core.2.x/html');

include_once('bootstrap.php');
sys::import('xaraya.core');

// TODO: don't load the whole core
xarCoreInit(XARCORE_SYSTEM_ALL);

function m($msg,$level=0) 
{ 
    $prefix = str_repeat('  ',$level);
    echo "$prefix - $msg\n"; 
}

if(!xarUserLogin('Admin','12345')) {
    throw new Exception("Authentication failed\n");
} else {
    m('Authenticated');
}


sys::import('xaraya.structures.sequences.queue');
sys::import('xaraya.structures.sequences.stack');
m('WHY IS THIS NOT USING THE LOVELY UNITTESTS?');
$l=0;
m('Testing DD queue',$l++);
$q = new Queue('dd',array('name'=>'masterq'));
$q->clear();
_tests($q,$l--);

m('Testing DD stack',$l++);
$q = new Stack('dd',array('name'=>'masterq'));
$q->clear();
_tests($q,$l--);

m('Testing array queue',$l++);
$q = new Queue();
$q->clear();
_tests($q,$l--);

m('Testing array stack',$l++);
$q = new Stack();
$q->clear();
_tests($q,$l--);
*/

function _tests($seq,$l=0)
{
    $seqName = get_class($seq);
    m("Operations on empty $seqName",$l++);
    m("Size of empty $seqName: ".$seq->size,$l);
    $s=$seq->empty?"yes":"NO?";
    m("Empty $seqName is empty: $s",$l);
    m("Popping from empty $seqName",$l);
    $seq->pop();
    $l--;

    $seq->clear();
    m("Pushing and popping 1 item into the $seqName",$l++);
    m("first",$l);
    $seq->push("first",$l--);
    m("Getting items back",$l++);
    m($seq->pop(),$l);
    $l--;

    m("Pushing and popping 3 items into the $seqName",$l++);
    m("first",$l);
    $seq->push("first");
    m("second",$l);
    $seq->push("second");
    m("third",$l);
    $seq->push("third");
    $l--;

    m("Getting items back",$l++);
    m($seq->pop(),$l);
    m($seq->pop(),$l);
    m($seq->pop(),$l);
    $l--;
}
?>