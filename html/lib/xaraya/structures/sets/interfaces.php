<?php
/**
 * @package core
 * @subpackage structures
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 */
/**
 * Interfaces for sets
 *
**/

interface iSet 
{
    // r (bool) empty : is this the empty set
    //   (size) size  : how many elements are in the set?
    /*   (bool) has   */public function has       ($value);
    /*   (Set)  union */public function union     ($left, $right = null);
    /*   (Set)  diff  */public function diff      ($left, $right = null);
    /*   (bool) sub   */public function subsetOf  (iSet $super);
    /*   (bool) sup   */public function supersetOf(iSet $sub);
}

?>