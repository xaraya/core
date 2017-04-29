<?php
/**
 * @package core\structures
 * @subpackage structures
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * Wrapper class for PHP date functions
 *
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

    public function __construct($hour=0,$minute=0,$second=0,$month=0,$day=0,$year=0,$timezone=null)
    {
        parent::__construct();
        $this->timestamp = mktime($hour,$minute,$second,$month,$day,$year);
        $this->year = $year;
        $this->month = $month;
        $this->day = $day;
        $this->hour = $hour;
        $this->minute = $minute;
        $this->second = $second;
        $this->servertz = empty($timezone) ? xarConfigVars::get(null, 'Site.Core.TimeZone') : $timezone;
        $this->setISODate($this->year,$this->month,$this->day);
        $this->setTime($this->hour,$this->minute,$this->second);
    }

    public function getTZOffset($timezone=null, $dst=1)
    {
        if (empty($timezone)) return 0;
        if ($dst) {
            $dt = new DateTime();

            $machinetz = date_default_timezone_get();
            $tzobject = new DateTimezone($machinetz);
            $dt->setTimezone($tzobject);
            $machineoffset = $dt->getOffset();

            $tzobject = new DateTimezone($this->servertz);
            $dt->setTimezone($tzobject);
            $baseoffset = $dt->getOffset();

            $tzobject = new DateTimezone($timezone);
            $dt->setTimezone($tzobject);
            $localoffset = $dt->getOffset();
        } else {
            $machinetz = date_default_timezone_get();
            $tzobject = new DateTimezone($machinetz);
            $machineoffset = $tzobject->getOffset($this);
            $tzobject = new DateTimezone($this->servertz);
            $baseoffset = $tzobject->getOffset($this);
            $tzobject = new DateTimezone($timezone);
            $localoffset = $tzobject->getOffset($this);
        }
        return $localoffset - ($baseoffset - $machineoffset);
    }

    public function setnow($timezone=null)
    {
        $this->timestamp = time();
        if (!empty($timezone)) $this->timestamp += $this->getTZOffset($timezone);
        $this->extract();
    }

    public function regenerate()
    {
        $this->timestamp = mktime($this->hour,$this->minute,$this->second,$this->month,$this->day,$this->year);
        $this->extract();
    }

    public function extract()
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

    public function DBtoTS($dbts)
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

    public function display($format='Y-m-d')
    {
        return date($format,$this->timestamp);
    }

    public function getTimearray()
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

    public function getTimestamp()  {  return $this->timestamp; }
    public function getDate($x)     {  return strtotime($x);    }
    public function getYear()       {  return $this->year;      }
    public function getYDay()
    {
        $datearray = getdate($this->timestamp); return $datearray['yday'];
    }

    // @todo add gets for weekdays etc.
    public function getMonth()       { return $this->month;    }
    public function getDay()         { return $this->day;      }
    public function getHour()        { return $this->hour;     }
    public function getMinute()      { return $this->minute;   }
    public function getSecond()      { return $this->second;   }

    public function setTimestamp($x)
    {
        $this->timestamp = $x; $this->extract();
    }

    public function setYear($x)   { $this->year   = $x; $this->regenerate(); }
    public function setMonth($x)  { $this->month  = $x; $this->regenerate(); }
    public function setDay($x)    { $this->day    = $x; $this->regenerate(); }
    public function setHour($x)   { $this->hour   = $x; $this->regenerate(); }
    public function setMinute($x) { $this->minute = $x; $this->regenerate(); }
    public function setSecond($x) { $this->second = $x; $this->regenerate(); }

    public function addYears($x)   { $this->year   += $x; $this->regenerate(); }
    public function addMonths($x)  { $this->month  += $x; $this->regenerate(); }
    public function addDays($x)    { $this->day    += $x; $this->regenerate(); }
    public function addHours($x)   { $this->hour   += $x; $this->regenerate(); }
    public function addMinutes($x) { $this->minute += $x; $this->regenerate(); }
    public function addSeconds($x) { $this->second += $x; $this->regenerate(); }
}

?>