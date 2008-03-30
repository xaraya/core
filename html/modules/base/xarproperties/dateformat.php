<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage base
 * @link http://xaraya.com/index.php/release/68.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Include the base class
 */
sys::import('modules.base.xarproperties.dropdown');
/**
 * Handle the date format property
 */
class DateFormatProperty extends SelectProperty
{
    public $id         = 33;
    public $name       = 'dateformat';
    public $desc       = 'Date Format';

    /**
     * Get Options
     *
     * Get a list of date formats
     */
    function getOptions()
    {
        $options = array(array('id' => '%m/%d/%Y %H:%M:%S', 'name' => xarML('12/31/2004 24:00:00')),
                               array('id' => '%d/%m/%Y %H:%M:%S', 'name' => xarML('31/12/2004 24:00:00')),
                               array('id' => '%Y/%m/%d %H:%M:%S', 'name' => xarML('2004/12/31 24:00:00')),
                               array('id' => '%d %m %Y %H:%M',    'name' => xarML('31 12 2004 24:00')),
                               array('id' => '%b %d %H:%M:%S',    'name' => xarML('12 31 24:00:00')),
                              );

        return $options;
    }
}
?>
