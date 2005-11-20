
/**
 * Date Format Property
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 */
/*
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Include the base class
 *
 */
include_once "modules/base/xarproperties/Dynamic_Select_Property.php";

/**
 * Class for the date format property
 *
 * @package dynamicdata
 */
class Dynamic_DateFormat_Property extends Dynamic_Select_Property
{
    function Dynamic_DateFormat_Property($args)
    {
        $this->Dynamic_Select_Property($args);
        if (count($this->options) == 0) {
            $this->options = array(
                                 array('id' => '%m/%d/%Y %H:%M:%S',     'name' => xarML('12/31/2004 24:00:00')),
                                 array('id' => '%d/%m/%Y %H:%M:%S',     'name' => xarML('31/12/2004 24:00:00')),
                                 array('id' => '%Y/%m/%d %H:%M:%S',     'name' => xarML('2004/12/31 24:00:00')),
                                 array('id' => '%d %m %Y %H:%M',        'name' => xarML('31 12 2004 24:00')),
                                 array('id' => '%b %d %H:%M:%S',        'name' => xarML('12 31 24:00:00')),
                             );
        }
    }

    // default methods from Dynamic_Select_Property

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
                              'id'         => 33,
                              'name'       => 'dateformat',
                              'label'      => 'Date Format',
                              'format'     => '33',
                              'validation' => '',
                              'source'         => '',
                              'dependancies'   => '',
                              'requiresmodule' => '',
                              'aliases'        => '',
                              'args'           => serialize($args),
                            // ...
                           );
        return $baseInfo;
     }

}
?>