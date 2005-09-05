<?php
/**
 * Dynamic Calendar Property
 *
 * @package dynamicdata
 * @subpackage properties
 */

/**
 * Class for dynamic calendar property
 *
 * @package dynamicdata
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
            if ($this->validation == 'datetime') {
                if (preg_match("/^\s*(\d\d\d\d)-(\d\d)-(\d\d)".
                        " (\d\d):(\d\d):(\d\d)\s*$/", $value, $matches)) {

                    /* check date is valid */
                    if (!checkdate($matches[2], $matches[3], $matches[1])) {
                        $this->invalid = xarML('date');
                        $this->value = null;
                        return false;
                    }

                    /* check time is valid */
                    if ($matches[4] < 0 || $matches[4] > 23 ||
                            $matches[5] < 0 || $matches[5] > 59 ||
                            $matches[6] < 0 || $matches[6] > 59) {
                        $this->invalid = xarML('time');
                        $this->value = null;
                        return false;
                    }

                    /* TODO: adjust for timezone */

                    /* reformat date */
                    $this->value = $matches[1]."-".$matches[2]."-".$matches[3]
                            ." ".$matches[4].":".$matches[5].":".$matches[6];
                } else {
                    $this->invalid = xarML('date');
                    $this->value = null;
                    return false;
                }
            } else if ($this->validation == 'date') {
                if (preg_match("/^\s*(\d\d\d\d)-(\d\d)-(\d\d)".
                        "( (\d\d):(\d\d):(\d\d))?\s*$/", $value, 
                        $matches)) {
                
                    /* check date is valid */
                    if (!checkdate($matches[2], $matches[3], $matches[1])) {
                        $this->invalid = xarML('date');
                        $this->value = null;
                        return false;
                    }

                    /* TODO: adjust for timezone */

                    /* reformat date */
                    $this->value = $matches[1]."-".$matches[2]."-".$matches[3];
                } else {
                    $this->invalid = xarML('date');
                    $this->value = null;
                    return false;
                }
            } else {
            
                $this->value = strtotime($value);
                // adjust for the user's timezone offset
                $this->value -= xarMLS_userOffset() * 3600;
            } /* string times */
        } else {
            $this->invalid = xarML('date');
            $this->value = null;
            return false;
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
        if (empty($id)) {
            $id = $name;
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
        if (!isset($dateformat)) {
            $dateformat = '%Y-%m-%d %H:%M:%S';
        }

        // include calendar javascript
        xarModAPIFunc('base','javascript','modulefile',
                      array('module' => 'base',
                            'filename' => 'calendar.js'));

/*
        $output = xarLocaleFormatDate('%a, %d %B %Y %H:%M:%S %Z', $value);
        $output .= '<br />';
*/
        $output = '';
        $timeval = xarLocaleFormatDate($dateformat, $value);
        $jsID = str_replace(array('[', ']'), '_', $id);
        $output .= '<input type="text" name="'.$name.'" id="'.$id.'_input" value="'.$timeval.'" size="20" maxlength="19" />
<a href="javascript:'.$jsID.'_cal.popup();"><img src="modules/base/xarimages/calendar.gif" width="16" height="16" border="0" alt="Click Here to Pick up the date" /></a>
<script language="JavaScript">
var '.$jsID.'_cal = new xar_base_calendar(document.getElementById("'.$id.'_input"), "'.xarServerGetBaseURI().'");
'.$jsID.'_cal.year_scroll = true;
'.$jsID.'_cal.time_comp = true;
</script>';
        if (!empty($this->invalid)) {
            $output .= ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>';
        }
        return $output;
    }

    function showOutput($args = array())
    {
         extract($args);
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
        if (!isset($dateformat)) {
            $dateformat = '%a, %d %B %Y %H:%M:%S %Z';
        }
        return xarLocaleFormatDate($dateformat, $value);
    }

}

?>
