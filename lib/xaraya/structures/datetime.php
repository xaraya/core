<?php
/**
 *
 * Wrapper class for PHP date functions
 *
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author Marc Lutolf
 *
 */

class XarDateTime extends DateTime
{
    public $year;
    public $month;
    public $day;
    public $hour;
    public $minute;
    public $second;
    public $timestamp;
    public $servertz;

    function __construct($hour=0,$minute=0,$second=0,$month=0,$day=0,$year=0,$timezone=null)
    {
        parent::__construct();
        $this->timestamp = mktime($hour,$minute,$second,$month,$day,$year);
        $this->year = $year;
        $this->month = $month;
        $this->day = $day;
        $this->hour = $hour;
        $this->minute = $minute;
        $this->second = $second;
        $this->servertz = empty($timezone) ? xarConfigVars::Get(null, 'Site.Core.TimeZone') : $timezone;
        $this->setISODate($this->year,$this->month,$this->day);
        $this->setTime($this->hour,$this->minute,$this->second);
    }

    function getTZOffset($timezone=null)
    {
        if (empty($timezone)) return 0;
        $machinetz = date_default_timezone_get();
        $tzobject = new DateTimezone($machinetz);
        $machineoffset = $tzobject->getOffset($this);
        $tzobject = new DateTimezone($this->servertz);
        $baseoffset = $tzobject->getOffset($this);
        $tzobject = new DateTimezone($timezone);
        $localoffset = $tzobject->getOffset($this);
        return $localoffset - ($baseoffset - $machineoffset);
    }

    function setnow($timezone=null)
    {
        $this->timestamp = time();
        if (!empty($timezone)) $this->timestamp += $this->getTZOffset($timezone);
        $this->extract();
    }

    function regenerate()
    {
        $this->timestamp = mktime($this->hour,$this->minute,$this->second,$this->month,$this->day,$this->year);
        $this->extract();
    }

    function extract()
    {
        $datearray    = getdate($this->timestamp);
        $this->year   = $datearray['year'];
        $this->month  = $datearray['mon'];
        $this->day    = $datearray['mday'];
        $this->hour   = $datearray['hours'];
        $this->minute = $datearray['minutes'];
        $this->second = $datearray['seconds'];
        $this->setISODate($this->year,$this->month,$this->day);
        $this->setTime($this->hour,$this->minute,$this->second);
    }

    function DBtoTS($dbts)
    {
        if (preg_match('/^\d{4}/',$dbts)) {
            $this->year =   substr($dbts,1,4);
            $this->month =  trim(substr($dbts,6,2),"0");
            $this->day =    trim(substr($dbts,9,2),"0");
            $this->hour =   trim(substr($dbts,12,2),"0");
            $this->minute = trim(substr($dbts,15,2),"0");
            $this->second = trim(substr($dbts,18,2),"0");
            $this->regenerate();
        } else {
            if ($dbts != "") {
                $guess = strtotime($dbts);
                if ($guess < 0) $guess = 0;
                }
            else {
                $guess = 0;
            }
            $this->setTimestamp($guess);
        }
    }

    function display($format='Y-m-d')
    {
        return date($format,$this->timestamp);
    }

    function getTimearray()
    {
        return array(
                    'year' => $this->year,
                    'month' => $this->month,
                    'day' => $this->day,
                    'hour' => $this->hour,
                    'minute' => $this->minute,
                    'second' => $this->second,
                );
    }

    function getTimestamp()  {  return $this->timestamp; }
    function getDate($x)     {  return strtotime($x);    }
    function getYear()       {  return $this->year;      }
    function getYDay()
    {
        $datearray = getdate($this->timestamp); return $datearray['yday'];
    }

    // @todo add gets for weekdays etc.
    function getMonth()       { return $this->month;    }
    function getDay()         { return $this->day;      }
    function getHour()        { return $this->hour;     }
    function getMinute()      { return $this->minute;   }
    function getSecond()      { return $this->second;   }

    function setTimestamp($x)
    {
        $this->timestamp = $x; $this->extract();
    }

    function setYear($x)   { $this->year   = $x; $this->regenerate(); }
    function setMonth($x)  { $this->month  = $x; $this->regenerate(); }
    function setDay($x)    { $this->day    = $x; $this->regenerate(); }
    function setHour($x)   { $this->hour   = $x; $this->regenerate(); }
    function setMinute($x) { $this->minute = $x; $this->regenerate(); }
    function setSecond($x) { $this->second = $x; $this->regenerate(); }

    function addYears($x)   { $this->year   += $x; $this->regenerate(); }
    function addMonths($x)  { $this->month  += $x; $this->regenerate(); }
    function addDays($x)    { $this->day    += $x; $this->regenerate(); }
    function addHours($x)   { $this->hour   += $x; $this->regenerate(); }
    function addMinutes($x) { $this->minute += $x; $this->regenerate(); }
    function addSeconds($x) { $this->second += $x; $this->regenerate(); }
}

?>
