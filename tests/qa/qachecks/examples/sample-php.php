<?php
/**
 * Xaraya
 *
 * This file just contains a whole lot of php, good and bad that I can use to
 * figure out the best way to find common errors.
 *
 * The numbers in here are based on the Xaraya Code Review Checklist v0.2 
 */

/* 2.1.5 don't use die() or exit()!... except it should still be okay in a
 * comment really. */
die();
exit();
$exitToken = 's';

/* 2.3.2 says don't use echo() and print() */
echo '';
print '';
// pass the printer ribbon

/* 2.3.2 no non-templated output */
echo '<html>
  <body>';

/* 2.4.3 dbconn */
list($foo) = xarDBGetConn();
$dbconn = xarDBGetConn();

/* 2.6.1 use xarInclude */
xarInclude ('hello.php');
include('hello.php');
 include_once('hello.php');
$a = 6; include('hello.php');
$a = 6; include('hello.php'); $x = 3; 

/* 3.1.2 here is an example of using = instead of == for comparisons */
if ($a = 1) { 
    $a = 6; 
}

/* the correct form is */
if ($a == 1) { 
    $a = 6; 
}

/* this is also correct */
for ($x = 1; $x < 10; $x++) {}
 for ($x = 1; $x < 10; $x++) {}
// form 
 foo ($x = 1; $x < 10; $x++) {}
function ($args = array());
 function ($args = array());

/* 3.2.4 use include in preference of include */
include_once('foobar');
include('foobar');
 require('foobar');
   $a = 3; require('x');
   require_once('x');

/* 3.3.2 TODO: check for
 todo
  FixME, or
  checkME */

/* 3.4.1 use <?php over <? */ ?>
<? is no good ?>
<?php is okay ?>
<?= "no good either!" ?>
<?xml could still be valid though ?>

# 5.1.4 no perl style comments
 # please!
/* 5.2.1 don't use tabs for indenting */
	$x = 1;
    $y = 1;
   	     $x =2;
  	$x =  3;

/* 5.2.2 also, indents are supposed to be in multiples of four spaces
   
    this is valid
        so is this
            and this
 this is bad indenting
   this is also bad indenting
         as is this */
    /** 
     * Comments can be a bit more
     * tricky.
     */

// 5.2.4 No windows line endings!
// please!

/* 5.2.6 functions use one-true-brace convention */
function bad_foobar() {

}

function good_foobar()
{

}

class BadBrace {

}
class TrueBrace
{

}
?>

