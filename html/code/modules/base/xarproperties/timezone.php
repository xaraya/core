<?php
/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/68.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Include the base class
 */
sys::import('modules.base.xarproperties.dropdown');

/**
 * Handle the timezone property
 */
class TimeZoneProperty extends SelectProperty
{
    public $id         = 32;
    public $name       = 'timezone';
    public $desc       = 'Time Zone';

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'base';
        $this->template  = 'timezone';
    }

    public function showOutput(Array $data = array())
    {
        if (!isset($data['value'])) $data['value'] = $this->value;
        $zone = new DateTimeZone($data['value']);
        $datetime = new DateTime('now',$zone);
        $data['offset'] = $zone->getOffset($datetime)/3600;
        return parent::showOutput($data);
    }

    function getOptions()
    {
        if (count($this->options) > 0) {
            return $this->options;
        }
        $zones = DateTimeZone::listIdentifiers();
        $options = array();
        foreach ($zones as $name) {
            $zone = new DateTimeZone($name);
            $datetime = new DateTime('now',$zone);
            $options[] = array('id' => $name, 'name' => $name, 'offset' => $zone->getOffset($datetime));
        }
        return $options;
    }
}
?>