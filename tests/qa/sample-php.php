<?php
/**
 * This file just contains a whole lot of php, good and bad that I can use to
 * figure out the best way to find common errors.
 *
 * The numbers in here are based on the Xaraya Code Review Checklist v0.1 (see
 * http://www.ninthave.net/docs/81
 */

/* 1.3 here is an example of using = instead of == for comparisons */
if ($a = 1) { 
    $a = 6; 
}

/* 1.3 the correct form is */
if ($a == 1) { 
    $a = 6; 
}

/* 1.4 here we are checking for variable names in templates */
/* template blah blah #$hello# is how you
   are supposed to do it, or you might be calling 
   a function like #xarModURL('base', 'user', 'main')#, the wrong
   way to do it is to just have #hello# without the dollar sign. */

/* 1.8 don't use die() or exit()!... except it should still be okay in a
 * comment really. */
die();
exit();

/* 2.1 says don't use echo() and print() */
echo '';
print '';

/* 2.2 no non-templated output */
echo '<html>
  <body>';

/* 2.10 no use of <? instead of
   <?php .... although we would still like to have
   <?xml which is valid */

/* 2.11 don't use tabs like at the end of this sentance.	*/
/* also, indents are supposed to be in multiples of four spaces
   
    this is valid
       so is this
            and this
 this is bad indenting
   this is also bad indenting
         as is this */


// 2.13 No windows line endings!
// please!

# 2.14 no perl style comments
 # please!

/* 2.15 use xarInclude */
xarInclude ('hello.php');
include('hello.php');
 include_once('hello.php');
$a = 6; include('hello.php');

/* 2.18 functions use one-true-brace convention */
function bad_foobar() {

}

function good_foobar()
{

}
?>

