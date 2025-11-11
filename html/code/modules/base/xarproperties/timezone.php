<?php

/**
 * Include the base class
 */
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
 * This property displays a dropdown of time zones
 */
class TimeZoneProperty extends SelectProperty
{
    public $id         = 32;
    public $name       = 'timezone';
    public $desc       = 'Time Zone';

    public function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'base';
        $this->template  = 'timezone';
    }
    /**
     * Display a timezone of region
     *
     * @param array<string, mixed> $data An array of input parameters
     * @return string     HTML markup to display the property for output on a web page
     */
    public function showOutput(array $data = [])
    {
        if (!isset($data['value'])) {
            $data['value'] = $this->value;
        }
        $zone = new DateTimeZone($data['value']);
        $datetime = new DateTime('now', $zone);
        $data['offset'] = $zone->getOffset($datetime) / 3600;
        return parent::showOutput($data);
    }
    /**
        * Get Options
        *
        * Get a array of timezones
        */
    public function getOptions()
    {
        if (count($this->options) > 0) {
            return $this->options;
        }
        $zones = DateTimeZone::listIdentifiers();
        $options = [];
        foreach ($zones as $name) {
            $zone = new DateTimeZone($name);
            $datetime = new DateTime('now', $zone);
            $options[] = ['id' => $name, 'name' => $name, 'offset' => $zone->getOffset($datetime)];
        }
        return $options;
    }
}
