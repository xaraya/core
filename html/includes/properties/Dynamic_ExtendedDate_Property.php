<?php
/**
 * @package dynamicdata
 * @subpackage properties
 * @copyright (C) 2004 by Ninth Avenue Software Pty Ltd
 * @link http://www.ninthave.net
 * @author Roger Keays <roger.keays@ninthave.net>
 */

include_once "modules/base/xarproperties/Dynamic_Calendar_Property.php";

/**
 * The extended date property converts the value provided by the javascript
 * calendar into a universal YYYY-MM-DD format for storage in most databases
 * supporting the 'date' type. 
 *
 * The problem with the normal Calendar property is that it converts
 * everything into a UNIX timestamp, and for most C librarys this does not
 * include dates before 1970. (see Xaraya bugs 2013 and 1428)
 */
class Dynamic_ExtendedDate_Property extends Dynamic_Calendar_Property
{
    /**
     * We allow two validations: date, and datetime (corresponding to the
     * database's date and datetime data types.
     *
     * We also don't make any modifications for the timezone (too hard).
     */
    function validateValue($value = null)
    {
        if (empty($this->validation)) {
            $this->validation = 'datetime';
        }
        /* sample value: 2004-06-18 18:47:33 */
        if (!empty($value) &&

            /* check it matches the correct regexp */
            ($this->validation == 'date' &&
            preg_match('/\d{4}-\d{1,2}-\d{1,2}/', $value)) ||

            ($this->validation == 'datetime' &&
            preg_match('/\d{4}-\d{1,2}-\d{1,2} \d{1,2}:\d{1,2}:\d{1,2}/', $value))
            ) {

            /* TODO: use xaradodb to format the date */
            $this->value = $value;
            return true;
        } else {
            $this->invalid = xarML('date');
            $this->value = null;
            return false;
        }
    } /* validateValue */


    /**
     * Show the output according to the requested dateformat.
     */
    function showOutput($args = array())
    {
        extract($args);

        $data = array();

        if (!isset($value)) {
            $value = $this->value;
        }

        /* default time is unspecified */
        if (empty($value)) {
            $value = -1;
        } 

        if (!isset($dateformat)) {
            if ($this->validation == 'date') {
                $dateformat = '%a, %d %B %Y %Z';
            } else {
                $dateformat = '%a, %d %B %Y %H:%M:%S %Z';
            }
        }

        /* TODO: format the date properly, and use templates */
        if (preg_match("/(\d{4})-(\d{1,2})-(\d{1,2})/", $value,
            $matches)) {
            return $matches[3].'/'.$matches[2].'/'.$matches[1];
        } else {
            return $value;
        }
    } /* showOutput */

    /**
     * Get the base information for this property.
     *
     * @returns array
     * @return base information for this property
     **/
     function getBasePropertyInfo()
     {
         $args = array();
         $baseInfo = array(
                              'id'         => 47,
                              'name'       => 'extendeddate',
                              'label'      => 'Extended Date',
                              'format'     => '47',
                              'validation' => '',
                              'source'         => '',
                              'dependancies'   => '',
                              'requiresmodule' => 'base',
                              'aliases'        => '',
                              'args'           => serialize($args),
                            // ...
                           );
        return $baseInfo;
     }

}


?>
