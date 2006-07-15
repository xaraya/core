<?php
/**
 * Interfaces for sets
 *
 */

interface iSet 
{
    // r (bool) empty : is this the empty set
    //   (size) size  : how many elements are in the set?
    /*   (bool) has   */public function has($value);
    /*   (Set)  union */public function union($left, $right = null);
    /*   (Set)  diff  */public function  diff($left, $right = null);
    /*   (bool) sub   */public function   subsetOf($super);
    /*   (bool) sup   */public function supersetOf($sub);
}

?>