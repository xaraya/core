<?php
/**
 * Time Since
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base Module
 * @link http://xaraya.com/index.php/release/151.html
*/
/* Returns a fomatted string of two of years/months/weeks/days/hours/minutes since a given time (unix timestamp).
 * @param int $args['stamp'] as a unix timestamp
 * @author - based on original by Natalie Downe http://blog.natbat.co.uk/archive/2003/Jun/14/time_since
 */
function base_userapi_timesince($args)
{
    extract($args);
    //expecting a var named $originaltime

    $mlyear = xarML('year');
    $mlmonth= xarML('month');
    $mlweek = xarML('week');
    $mlday  = xarML('day');
    $mlhour = xarML('hour');
    $mlminute = xarMl('minute');
    // array of time period chunks
    $chunks = array(
        array(60 * 60 * 24 * 365 , $mlyear),
        array(60 * 60 * 24 * 30 , $mlmonth),
        array(60 * 60 * 24 * 7, $mlweek),
        array(60 * 60 * 24 , $mlday),
        array(60 * 60 , $mlhour),
        array(60 , $mlminute),
    );

    $today = time(); /* Current unix time  */
    $since = $today - $stamp;
    
    // $j saves performing the count function each time around the loop
    for ($i = 0, $j = count($chunks); $i < $j; $i++) {
        
        $seconds = $chunks[$i][0];
        $name = $chunks[$i][1];
        
        // finding the biggest chunk (if the chunk fits, break)
        if (($count = floor($since / $seconds)) != 0) {
            // DEBUG print "<!-- It's $name -->\n";
            break;
        }
    }
    
    $print = ($count == 1) ? '1 '.$name : "$count {$name}s";
    
    if ($i + 1 < $j) {
        // now getting the second item
        $seconds2 = $chunks[$i + 1][0];
        $name2 = $chunks[$i + 1][1];
        
        // add second item if it's greater than 0
        if (($count2 = floor(($since - ($seconds * $count)) / $seconds2)) != 0) {
            $print .= ($count2 == 1) ? ', 1 '.$name2 : ", $count2 {$name2}s";
        }
    }
    return $print;
}
?>