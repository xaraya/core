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
        // default time is unspecified
        if (empty($value)) {
              $this->value = -1;
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
            // this returns -1 when we have an invalid date (e.g. on purpose)
            $this->value = strtotime($value);
            if ($this->value >= 0) {
                // adjust for the user's timezone offset
                $this->value -= xarMLS_userOffset() * 3600;
            }
        } else {
            $this->invalid = xarML('date');
            $this->value = null;
            return false;
        }
        // TODO: improve this
        // store values in a datetime field
        if ($this->validation == 'datetime') {
            $this->value = gmdate('Y-m-d H:i:s', $this->value);
        // store values in a date field
        } elseif ($this->validation == 'date') {
            $this->value = gmdate('Y-m-d', $this->value);
        }
        return true;
    }

//    function showInput($name = '', $value = null, $id = '', $tabindex = '')
    function showInput($args = array())
    {
        extract($args);
        $data = array();

        if (empty($name)) {
            $name = 'dd_'.$this->id;
        }
        if (empty($id)) {
            $id = $name;
        }
        if (!isset($value)) {
            $value = $this->value;
        }
        // default time is unspecified
        if (empty($value)) {
            $value = -1;
        } elseif (!is_numeric($value) && is_string($value)) {
            // assume dates are stored in UTC format
        // TODO: check if we still need to add "00" for PostgreSQL timestamps or not
            if (!preg_match('/[a-zA-Z]+/',$value)) {
                $value .= ' GMT';
            }
            // this returns -1 when we have an invalid date (e.g. on purpose)
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
        //$output = '';
        $timeval = xarLocaleFormatDate($dateformat, $value);
        $jsID = str_replace(array('[', ']'), '_', $id);
        /*
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
         */
        $data['baseuri']   =xarServerGetBaseURI();
        $data['dateformat']= $dateformat;
        $data['jsID']     = $jsID;
        $data['timeval']  = $timeval;
        $data['name']     = $name;
        $data['id']       = $id;
        $data['value']    = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);
        $data['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';

      $template="calendar";
      return xarTplModule('dynamicdata', 'admin', 'showinput', $data , $template);

        //return $output;
    }

    function showOutput($args = array())
    {
        extract($args);
        
        $data=array();
        
        if (!isset($value)) {
            $value = $this->value;
        }
        // default time is unspecified
        if (empty($value)) {
            $value = -1;
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
        //return xarLocaleFormatDate($dateformat, $value);

        $data['dateformat']=$dateformat;
        $data['value'] = $value;
        $data['returnvalue']= xarLocaleFormatDate($dateformat, $value);

        $template="calendar";
        return xarTplModule('dynamicdata', 'user', 'showoutput', $data ,$template);
    }

}

?>
