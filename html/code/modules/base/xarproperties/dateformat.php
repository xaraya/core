<?php
/**
 * Include the base class
 */
sys::import('modules.base.xarproperties.dropdown');
/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * This property displays a dropdown of date formats
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
        if (count($this->options) > 0) {
            return $this->options;
        }
        
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