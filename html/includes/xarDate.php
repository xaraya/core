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
 * @todo bring back possibility of time authorized keys
 * @todo this needs another place
 * @todo this needs documentation
 */

class xarDate 
{

    var $year;
    var $month;
    var $day;
    var $hour;
    var $minute;
    var $second;
    var $timestamp;

    function xarDate($hour=0,$minute=0,$second=0,$month=0,$day=0,$year=0) 
    {
        $this->timestamp = mktime($hour,$minute,$second,$month,$day,$year);
        $this->year = $year;
        $this->month = $month;
        $this->day = $day;
        $this->hour = $hour;
        $this->minute = $minute;
        $this->second = $second;
    }

    function setnow() 
    {
        $this->timestamp = time();
        $this->extract();
    }

    function regenerate() 
    {
        $this->timestamp = mktime($this->hour,$this->minute,$this->second,$this->month,$this->day,$this->year);
        $this->extract();
    }

    function extract() 
    {
        $datearray = getdate($this->timestamp);
        $this->year =   $datearray['year'];
        $this->month =  $datearray['mon'];
        $this->day =    $datearray['mday'];
        $this->hour =   $datearray['hours'];
        $this->minute = $datearray['minutes'];
        $this->second = $datearray['seconds'];
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

    function display($format) 
    {
        return date($format,$this->timestamp); 
    }
    
    function getTimestamp() 
    { 
        return $this->timestamp; 
    }
    
    function getDate($x)    
    { 
        return strtotime($x); 
    }
    
    function getYear()      
    { 
        return $this->year; 
    }
    
    function getYDay()      
    { 
        $datearray = getdate($this->timestamp); return $datearray['yday']; 
    }
    
    //TODO: add gets for weekdays etc.
    
    function getMonth()     
    { 
        return $this->month; 
    }
    
    function getDay()       
    { 
        return $this->day; 
    }
    
    function getHour()      
    { 
        return $this->hour; 
    }
    
    function getMinute()    
    { 
        return $this->minute; 
    }
    
    function getSecond()    
    { 
        return $this->second; 
    }
    
    function setTimestamp($x) 
    { 
        $this->timestamp = $x; $this->extract(); 
    }
    
    function setYear($x)    
    { 
        $this->year = $x; $this->regenerate(); 
    }
    
    function setMonth($x)   
    { 
        $this->month = $x; $this->regenerate(); 
    }
    
    function setDay($x)     
    { 
        $this->day = $x; $this->regenerate(); 
    }
    
    function setHour($x)    
    { 
        $this->hour = $x; $this->regenerate(); 
    }
    
    function setMinute($x)  
    { 
        $this->minute = $x; $this->regenerate(); 
    }
    
    function setSecond($x)  
    { 
        $this->second = $x; $this->regenerate(); 
    }
    
    function addYears($x)   
    { 
        $this->year = $this->year + $x; $this->regenerate(); 
    }
    
    function addMonths($x)  
    { 
        $this->month = $this->month + $x; $this->regenerate(); 
    }
    
    function addDays($x)    
    { 
        $this->day = $this->day + $x; $this->regenerate(); 
    }
    
    function addHours($x)   
    { 
        $this->hour = $this->hour + $x; $this->regenerate(); 
    }
    
    function addMinutes($x) 
    { 
        $this->minute = $this->minute + $x; $this->regenerate(); 
    }
    
    function addSeconds($x) 
    { 
        $this->second = $this->second + $x; $this->regenerate(); 
    }
}

?>
