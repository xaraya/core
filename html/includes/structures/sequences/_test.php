<?php

/** 
 * Test script for the sequence like stuff
 */
include dirname(__FILE__).'/stack.php';

function m($m){ echo $m."\n";}

m('Creating stack');
$stack= new Stack();
var_dump($stack);
m('Size: ' . $stack->size);
m('Empty: ' . $stack->empty);
m('Pushing 1 element');
$stack->push('Marcel');
var_dump($stack);
m('Stack size: ' . $stack->size);
m('Empty: ' . $stack->empty);
m($stack->pop());

?>