<?php
/**
 * @package modules
 * @copyright see the html/credits.html file in this release
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

    public function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;

        if (empty($value)) {
            // no timezone selected
            return true;

        } elseif (is_numeric($value)) {
            // keep old numeric format
            return true;

        } elseif (is_string($value)) {
            // check what kind of string we have here
            $out = @unserialize($value);
            if ($out !== false) {
                // we have a serialized value
                if (empty($out['timezone'])) {
                    $this->value = '';
                    return true;
                }
                $timezone = $out['timezone'];
            } else {
                // we have a text value
                $timezone = $value;
            }

        } elseif (is_array($value)) {
            if (empty($value['timezone'])) {
                $this->value = '';
                return true;
            }
            $timezone = $value['timezone'];
        }

        // check if the timezone exists
        $info = xarMod::apiFunc('base','user','timezones',
                              array('timezone' => $timezone));
        if (empty($info)) {
            $this->invalid = xarML('timezone');
            $this->value = null;
            return false;
        }
        list($hours,$minutes) = explode(':',$info[0]);
        // tz offset is in hours
        $offset = (float) $hours + (float) $minutes / 60;
        // save a serialized array with timezone and offset
        $value = array('timezone' => $timezone,
                       'offset'   => $offset);
        $this->value = serialize($value);
        return true;
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