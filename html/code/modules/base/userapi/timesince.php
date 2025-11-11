<?php

/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Base\UserApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Base\UserApi;

/**
 * base userapi timesince function
 * @extends MethodClass<UserApi>
 */
class TimesinceMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Returns a fomatted string of two of years/months/weeks/days/hours/minutes since a given time (unix timestamp).
     * @param array<string,mixed> $args array of optional parameters<br/>
     * integer  $args['stamp'] as a unix timestamp
     * @author - based on original by Natalie Downe http://blog.natbat.co.uk/archive/2003/Jun/14/time_since
     * @return string Formatted time string
     * @see UserApi::timesince()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        //expecting a var named $originaltime

        $mlyear = $this->ml('year');
        $mlmonth = $this->ml('month');
        $mlweek = $this->ml('week');
        $mlday  = $this->ml('day');
        $mlhour = $this->ml('hour');
        $mlminute = xarMl('minute');
        // array of time period chunks
        $chunks = [
            [60 * 60 * 24 * 365, $mlyear],
            [60 * 60 * 24 * 30, $mlmonth],
            [60 * 60 * 24 * 7, $mlweek],
            [60 * 60 * 24, $mlday],
            [60 * 60, $mlhour],
            [60, $mlminute],
        ];

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

        $print = ($count == 1) ? '1 ' . $name : "$count {$name}s";

        if ($i + 1 < $j) {
            // now getting the second item
            $seconds2 = $chunks[$i + 1][0];
            $name2 = $chunks[$i + 1][1];

            // add second item if it's greater than 0
            if (($count2 = floor(($since - ($seconds * $count)) / $seconds2)) != 0) {
                $print .= ($count2 == 1) ? ', 1 ' . $name2 : ", $count2 {$name2}s";
            }
        }
        return $print;
    }
}
