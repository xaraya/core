<?php
/**
 * Dynamic Calendar Property
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_Calendar_Property extends Dynamic_Property
{
    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        // default time is now
        if (empty($value)) {
            $this->value = time();
        } elseif (is_numeric($value)) {
            $this->value = $value;
        } elseif (is_array($value) && !empty($value['year'])) {
            if (!isset($value['sec'])) {
                $value['sec'] = 0;
            }
            $this->value = mktime($value['hour'],$value['min'],$value['sec'],
                                  $value['mon'],$value['mday'],$value['year']);
        } elseif (is_string($value)) {
            // assume dates are stored in UTC format
        // TODO: check if we still need to add "00" for PostgreSQL timestamps or not
            if (!preg_match('/[a-zA-Z]+/',$value)) {
                $value .= ' GMT';
            }
            $this->value = strtotime($value);
        } else {
            $this->invalid = xarML('date');
            $this->value = null;
            return false;
        }
        // TODO: improve this
        if ($this->validation == 'datetime') {
            $this->value = gmdate('Y-m-d H:i:s', $this->value);
        }
        return true;
    }

//    function showInput($name = '', $value = null, $id = '', $tabindex = '')
    function showInput($args = array())
    {
        extract($args);
        if (empty($name)) {
            $name = 'dd_'.$this->id;
        }
        if (!isset($value)) {
            $value = $this->value;
        }
        if (empty($value)) {
            $value = time();
        } elseif (!is_numeric($value) && is_string($value)) {
            // assume dates are stored in UTC format
        // TODO: check if we still need to add "00" for PostgreSQL timestamps or not
            if (!preg_match('/[a-zA-Z]+/',$value)) {
                $value .= ' GMT';
            }
            $value = strtotime($value);
        }

        $output = '';
    // TODO: adapt to local/user time !
        $output .= strftime('%a, %d %B %Y %H:%M:%S %Z', $value);
        $output .= '<br />';
        $localtime = localtime($value,1);
        $output .= xarML('Date') . ' <select name="'.$name.'[year]"'.
                   (!empty($id) ? ' id="'.$id.'"' : '') .
                   (!empty($tabindex) ? ' tabindex="'.$tabindex.'"' : '') . '>';
        if (empty($minyear)) {
            $minyear = $localtime['tm_year'] + 1900 - 2;
        }
        if (empty($maxyear)) {
            $maxyear = $localtime['tm_year'] + 1900 + 2;
        }
        for ($i = $minyear; $i <= $maxyear; $i++) {
            if ($i == $localtime['tm_year'] + 1900) {
                $output .= '<option selected>' . $i;
            } else {
                $output .= '<option>' . $i;
            }
        }
        $output .= '</select> - <select name="'.$name.'[mon]">';
        for ($i = 1; $i <= 12; $i++) {
            if ($i == $localtime['tm_mon'] + 1) {
                $output .= '<option selected>' . $i;
            } else {
                $output .= '<option>' . $i;
            }
        }
        $output .= '</select> - <select name="'.$name.'[mday]">';
        for ($i = 1; $i <= 31; $i++) {
            if ($i == $localtime['tm_mday']) {
                $output .= '<option selected>' . $i;
            } else {
                $output .= '<option>' . $i;
            }
        }
        $output .= '</select> ';
        $output .= xarML('Time') . ' <select name="'.$name.'[hour]">';
        for ($i = 0; $i < 24; $i++) {
            if ($i == $localtime['tm_hour']) {
                $output .= '<option selected>' . sprintf("%02d",$i);
            } else {
                $output .= '<option>' . sprintf("%02d",$i);
            }
        }
        $output .= '</select> : <select name="'.$name.'[min]">';
        for ($i = 0; $i < 60; $i++) {
            if ($i == $localtime['tm_min']) {
                $output .= '<option selected>' . sprintf("%02d",$i);
            } else {
                $output .= '<option>' . sprintf("%02d",$i);
            }
        }
        $output .= '</select> : <select name="'.$name.'[sec]">';
        for ($i = 0; $i < 60; $i++) {
            if ($i == $localtime['tm_sec']) {
                $output .= '<option selected>' . sprintf("%02d",$i);
            } else {
                $output .= '<option>' . sprintf("%02d",$i);
            }
        }
        $output .= '</select> ';
        if (!empty($this->invalid)) {
            $output .= ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>';
        }
        return $output;
    }

    function showOutput($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        // default time is now
        if (empty($value)) {
            $value = time();
        } elseif (!is_numeric($value) && is_string($value)) {
            // assume dates are stored in UTC format
        // TODO: check if we still need to add "00" for PostgreSQL timestamps or not
            if (!preg_match('/[a-zA-Z]+/',$value)) {
                $value .= ' GMT';
            }
            $value = strtotime($value);
        }
    // TODO: adapt to local/user time !
        return strftime('%a, %d %B %Y %H:%M:%S %Z', $value);
    }

}

?>