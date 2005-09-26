<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 */
/**
 * Adjust timestamp according to DST rules (based on modules/timezone/tzdata.php)
 * Check if we need to adjust timestamp according to DST rules (already with timezone offset)
 *
 * @author mikespub
 * @author the Base module development team
 * @param $args['time'] integer timestamp we want to adjust for daylight saving
 * @param $args['timezone'] string the timezone we're in, or
 * @param $args['offset'] integer the timezone offset, and
 * @param $args['rule'] string the DST rule we want to use or
 * @param $args['dstinfo'] array the array of DST information we want to use
 * @return integer the (possible) adjustment factor for daylight saving in hours
 */
function base_userapi_dstadjust($args)
{
    static $cachedst = array();
    extract($args);

    if (!isset($time)) {
        $time = time();
    }
    $year = gmdate('Y',$time);

    // check if we need to retrieve some additional information
    if (isset($dstinfo) && isset($offset)) {
        // some cache id for the current DST rules
        $dstid = dechex(crc32(serialize($dstinfo))) . $offset;
        // we already have the DST information

    } elseif (isset($rule) && isset($offset)) {
        // some cache identifier for the current DST rules
        $dstid = $rule . $offset;
        if (!isset($cachedst[$dstid][$year])) {
            // get the DST information for this rule
            $dstinfo = xarModAPIFunc('base','user','dstrules',
                                     array('rule' => $rule,
                                           'time' => $time));
        }

    } elseif (isset($timezone)) {
        // some cache id for the current DST rules
        $dstid = $timezone;
        if (!isset($cachedst[$dstid][$year])) {
            // get the timezone information
            $tzinfo = xarModAPIFunc('base','user','timezones',
                                    array('timezone' => $timezone,
                                          'time'     => $time));
            // Check that a timezone was returned
            if (!empty($tzinfo)) {
                // Retrieve timezone information
                list($hours,$minutes) = explode(':',$tzinfo[0]);
                $offset = (float) $hours + (float) $minutes / 60;
                if (!empty($tzinfo[1]) && $tzinfo[1] != '-') {
                    // get the DST information for this rule
                    $dstinfo = xarModAPIFunc('base','user','dstrules',
                                             array('rule' => $tzinfo[1],
                                                   'time' => $time));
                }
            }
        }
    }

    // check if we already calculated the start, end and adjust values for this year
    if (isset($cachedst[$dstid][$year])) {
        //echo 'cached';
        if (empty($cachedst[$dstid][$year])) {
            return 0;
        }
        $start  = $cachedst[$dstid][$year]['start'];
        $end    = $cachedst[$dstid][$year]['end'];
        $adjust = $cachedst[$dstid][$year]['adjust'];
        if ($end > $start) { // northern hemisphere
            if ($time >= $start && $time < $end) {
                return $adjust;
            }
        } else { // southern hemisphere
            if ($time >= $start || $time < $end) {
                return $adjust;
            }
        }
        return 0;
    }

    $cachedst[$dstid][$year] = array();

    if (empty($dstinfo)) {
        return 0;
    }

// Sample format of DST info :
//    array(
//        array('1981', 'max', '-', 'Mar', 'lastSun', '1:00u', '1:00', 'S'),
//        array('1996', 'max', '-', 'Oct', 'lastSun', '1:00u', '0', '-'),
//    );

    $days = array('Sun'=>0, 'Mon'=>1, 'Tue'=>2, 'Wed'=>3, 'Thu'=>4, 'Fri'=>5, 'Sat'=>6);
    $months = array('Jan'=>1, 'Feb'=>2, 'Mar'=>3, 'Apr'=>4, 'May'=>5, 'Jun'=>6,
                    'Jul'=>7, 'Aug'=>8, 'Sep'=>9, 'Oct'=>10, 'Nov'=>11, 'Dec'=>12);

    foreach ($dstinfo as $list) {

        $month = $months[$list[3]];

        if (is_numeric($list[4])) {
            // day of the month we're looking for
            $day = (int) $list[4];

        } elseif (preg_match('/^last(\w+)/',$list[4],$matches)) {
            // day of the week we're looking for
            $weekday = $days[$matches[1]];
            // last day of the current month
            if ($month == 12) {
                // day 0 of the next month gives the last day of the current month
                $lastdate = gmmktime(0,0,0,1,0,$year+1);
            } else {
                // day 0 of the next month gives the last day of the current month
                $lastdate = gmmktime(0,0,0,$month+1,0,$year);
            }
            // day of the week of the last day
            $lastweekday = gmdate('w',$lastdate);
            // move back to the weekday we want
            if ($lastweekday >= $weekday) {
                $newdate = $lastdate - (($lastweekday - $weekday) * 60 * 60 * 24);
            } else {
                $newdate = $lastdate - ((7 + $lastweekday - $weekday) * 60 * 60 * 24);
            }
            $day = gmdate('d',$newdate);

        } elseif (preg_match('/^first(\w+)/',$list[4],$matches)) {
            // day of the week we're looking for
            $weekday = $days[$matches[1]];
            // first day of the current month
            $firstdate = gmmktime(0,0,0,$month,1,$year);
            // day of the week of the first day
            $firstweekday = gmdate('w',$firstdate);
            // move forward to the weekday we want
            if ($firstweekday <= $weekday) {
                $newdate = $firstdate + (($weekday - $firstweekday) * 60 * 60 * 24);
            } else {
                $newdate = $firstdate + ((7 + $weekday - $firstweekday) * 60 * 60 * 24);
            }
            $day = gmdate('d',$newdate);

        } elseif (preg_match('/^(\w+)>=(\d+)/',$list[4],$matches)) {
            // day of the week we're looking for
            $weekday = $days[$matches[1]];
            // day of the month to start with
            $startday = $matches[2];
            // start day for the current month
            $startdate = gmmktime(0,0,0,$month,$startday,$year);
            // day of the week of the start day
            $startweekday = gmdate('w',$startdate);
            // move forward to the weekday we want
            if ($startweekday <= $weekday) {
                $newdate = $startdate + (($weekday - $startweekday) * 60 * 60 * 24);
            } else {
                $newdate = $startdate + ((7 + $weekday - $startweekday) * 60 * 60 * 24);
            }
            $day = gmdate('d',$newdate);

        } elseif (preg_match('/^(\w+)<=(\d+)/',$list[4],$matches)) {
            // day of the week we're looking for
            $weekday = $days[$matches[1]];
            // day of the month to end with
            $endday = $matches[2];
            // end day for the current month
            $enddate = gmmktime(0,0,0,$month,$endday,$year);
            // day of the week of the end day
            $endweekday = gmdate('w',$enddate);
            // move back to the weekday we want
            if ($endweekday >= $weekday) {
                $newdate = $enddate - (($endweekday - $weekday) * 60 * 60 * 24);
            } else {
                $newdate = $enddate - ((7 + $endweekday - $weekday) * 60 * 60 * 24);
            }
            $day = gmdate('d',$newdate);

        } else {
            // unknown rule
            return 0;
        }

        if (preg_match('/(\d+):(\d+)(\w?)/',$list[5],$matches)) {
            $hour = (int) $matches[1];
            $minute = (int) $matches[2];
            $type = $matches[3];
            $test = gmmktime($hour,$minute,0,$month,$day,$year);
            if (empty($type)) {
                // we'll need to adjust the end time
            } elseif ($type == 's') {
            } elseif ($type == 'u') {
                $test += $offset * 3600;
            } else {
                // unknown rule
                return 0;
            }
        } else {
            // unknown rule
            return 0;
        }

        if ($list[6] == '0') {
            $end = $test;
        } else {
            $start = $test;
            list($hours,$minutes) = explode(':',$list[6]);
            $adjust = (float) $hours + (float) $minutes / 60;
        }
    }
    if (!isset($start) || !isset($end)) {
        return 0;
    }
    if (empty($type)) {
        // if daylight saving ends at 01:00 DST, it's actually still 00:00 standard time
        $end -= $adjust * 3600;
    }
    $cachedst[$dstid][$year] = array('start'  => $start,
                                     'end'    => $end,
                                     'adjust' => $adjust);

    // see if we need to adjust the standard time for daylight saving or not
    if ($end > $start) { // northern hemisphere (time is between start and end this year)
        if ($time >= $start && $time < $end) {
            return $adjust;
        }
    } else { // southern hemisphere (time is before end or after start this year)
        if ($time >= $start || $time < $end) {
            return $adjust;
        }
    }

    return 0;
}
?>
