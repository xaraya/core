<?php
/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 */

/**
 * This property displays a textbox for date/time input	
 * @todo Review this
 */
class CalendarProperty extends DataProperty
{
    public $id         = 8;
    public $name       = 'calendar';
    public $desc       = 'Calendar';
    public $reqmodules = array('base');

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'base';
        $this->filepath  = 'modules/base/xarproperties';
    }
	
	/**
	 * Validate the date and Time
	 *
	 * @return bool Returns true if the value passes all validation checks; otherwise returns false.
	 */
    public function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;

        // default time is unspecified
        if (empty($value)) {
              $this->value = -1;
        } elseif (is_numeric($value)) {
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
            // starting with PHP 5.1.0, strtotime returns false instead of -1
            if ($this->value === false) $this->value = -1;
            if ($this->value >= 0) {
                // adjust for the user's timezone offset
                $this->value -= xarMLS::userOffset($this->value) * 3600;
            }
        } else {
            $this->invalid = xarML('date: #(1)', $this->name);
            xarLog::message($this->invalid, xarLog::LEVEL_ERROR);
            $this->value = null;
            return false;
        }
        // TODO: improve this
        // store values in a datetime field
        if ($this->configuration == 'datetime') {
            $this->value = gmdate('Y-m-d H:i:s', $this->value);
        // store values in a date field
        } elseif ($this->configuration == 'date') {
            $this->value = gmdate('Y-m-d', $this->value);
        }
        return true;
    }
	
	/**
	 * Display a textbox for input
	 * 
	 * @param  array data An array of input parameters
	 * @return string     HTML markup to display the property for input on a web page
	 */
    public function showInput(Array $data = array())
    {
        extract($data);
        if (!isset($value)) $value = $this->value;
        if (!isset($id)) $id = 'dd_'.$this->id;

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
            // starting with PHP 5.1.0, strtotime returns false instead of -1
            if ($value === false) $value = -1;
        }
        if (!isset($dateformat)) {
            $dateformat = '%Y-%m-%d %H:%M:%S';
            if ($this->configuration == 'date') {
                $dateformat = '%Y-%m-%d';
            } else {
                $dateformat = '%Y-%m-%d %H:%M:%S';
            }
        }

        // $timeval = xarLocaleFormatDate($dateformat, $value);
        $data['baseuri']    = xarServer::getBaseURI();
        $data['dateformat'] = $dateformat;
        $data['jsID']       = str_replace(array('[', ']'), '_', $id);
        // $data['timeval']    = $timeval;
        $data['id'] = $id;
        $data['value']      = $value;
        return parent::showInput($data);
    }
	
	/**
	 * Display a textbox for output
	 * 
	 * @param  array data An array of input parameters
	 * @return string     HTML markup to display the property for output on a web page
	 */
    public function showOutput(Array $data = array())
    {
        extract($data);

        if (!isset($value)) $value = $this->value;

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
            // starting with PHP 5.1.0, strtotime returns false instead of -1
            if ($value === false) $value = -1;
        }
        if (!isset($dateformat)) {
            $dateformat = '%a, %d %B %Y %H:%M:%S %Z';
        }

        $data['dateformat'] = $dateformat;
        $data['value'] = $value;
        // $data['returnvalue']= xarLocaleFormatDate($dateformat, $value);
        return parent::showOutput($data);
    }
	
	/**
	 * Display the Configuration (uses calendar template as default, allow template override by child classes)
	 * 
	 * @param  array data An array of input parameters 
	 * @return string     HTML markup to display the property for output on a web page
	 */
    public function showConfiguration(Array $args = array())
    {
        extract($args);

        $data = array();
        $data['name']       = !empty($name) ? $name : 'dd_'.$this->id;
        $data['id']         = !empty($id)   ? $id   : 'dd_'.$this->id;
        $data['tabindex']   = !empty($tabindex) ? $tabindex : 0;
        $data['invalid']    = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';

        if (isset($validation)) {
            $this->configuration = $validation;
        }
        if (empty($this->configuration) || $this->configuration == 'datetime' || $this->configuration == 'date') {
            $data['dbformat'] = $this->configuration;
            $data['other'] = '';
        } else {
            $data['dbformat'] = '';
            $data['other'] = $this->configuration;
        }
        // Note : timestamp is not an option for ExtendedDate
        $data['class'] = get_class($this);

        // allow template override by child classes
        if (empty($template)) {
            $template = 'calendar';
        }
        return xarTpl::property('base', $template, 'configuration', $data);
    }
	
	/**
	 * Update the Configuration 
	 * 
	 * Validate the data and  save it in $this->configuration
	 */
    public function updateConfiguration(Array $args = array())
    {
        extract($args);

        // in case we need to process additional input fields based on the name
        $name = empty($name) ? 'dd_'.$this->id : $name;
        // do something with the validation and save it in $this->configuration
        if (isset($validation)) {
            if (is_array($validation)) {
                if (!empty($validation['other'])) {
                    $this->configuration = $validation['other'];

                } elseif (isset($validation['dbformat'])) {
                    $this->configuration = $validation['dbformat'];

                } else {
                    $this->configuration = '';
                }
            } else {
                $this->configuration	 = $validation;
            }
        }

        // tell the calling function that everything is OK
        return true;
    }
}
?>