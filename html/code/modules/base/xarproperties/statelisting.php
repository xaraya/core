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
 * @author John Cox
 */


/**
 * Handle the StateList property
 *
 * This property displays a dropdown of US, Canadian and Australian states
 */
class StateListProperty extends SelectProperty
{
    public $id         = 43;
    public $name       = 'statelisting';
    public $desc       = 'State Dropdown';

    public function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->template  = 'statelisting';
    }


    /**
    * Get Options
    *
    * Get a list of States
    */
    public function getOptions()
    {
        if (count($this->options) > 0) {
            return $this->options;
        }

        $options[] = ['id' => 'Alabama', 'name' => 'Alabama'];
        $options[] = ['id' => 'Alaska', 'name' => 'Alaska'];
        $options[] = ['id' => 'Arizona', 'name' => 'Arizona'];
        $options[] = ['id' => 'Arkansas', 'name' => 'Arkansas'];
        $options[] = ['id' => 'California', 'name' => 'California'];
        $options[] = ['id' => 'Colorado', 'name' => 'Colorado'];
        $options[] = ['id' => 'Connecticut', 'name' => 'Connecticut'];
        $options[] = ['id' => 'Delaware', 'name' => 'Delaware'];
        $options[] = ['id' => 'District of Columbia', 'name' => 'District of Columbia'];
        $options[] = ['id' => 'Florida', 'name' => 'Florida'];
        $options[] = ['id' => 'Georgia', 'name' => 'Georgia'];
        $options[] = ['id' => 'Hawaii', 'name' => 'Hawaii'];
        $options[] = ['id' => 'Idaho', 'name' => 'Idaho'];
        $options[] = ['id' => 'Illinois', 'name' => 'Illinois'];
        $options[] = ['id' => 'Indiana', 'name' => 'Indiana'];
        $options[] = ['id' => 'Iowa', 'name' => 'Iowa'];
        $options[] = ['id' => 'Kansas', 'name' => 'Kansas'];
        $options[] = ['id' => 'Kentucky', 'name' => 'Kentucky'];
        $options[] = ['id' => 'Louisiana', 'name' => 'Louisiana'];
        $options[] = ['id' => 'Maine', 'name' => 'Maine'];
        $options[] = ['id' => 'Maryland', 'name' => 'Maryland'];
        $options[] = ['id' => 'Massachusetts', 'name' => 'Massachusetts'];
        $options[] = ['id' => 'Michigan', 'name' => 'Michigan'];
        $options[] = ['id' => 'Minnesota', 'name' => 'Minnesota'];
        $options[] = ['id' => 'Mississippi', 'name' => 'Mississippi'];
        $options[] = ['id' => 'Missouri', 'name' => 'Missouri'];
        $options[] = ['id' => 'Montana', 'name' => 'Montana'];
        $options[] = ['id' => 'Nebraska', 'name' => 'Nebraska'];
        $options[] = ['id' => 'Nevada', 'name' => 'Nevada'];
        $options[] = ['id' => 'New Hampshire', 'name' => 'New Hampshire'];
        $options[] = ['id' => 'New Jersey', 'name' => 'New Jersey'];
        $options[] = ['id' => 'New Mexico', 'name' => 'New Mexico'];
        $options[] = ['id' => 'New York', 'name' => 'New York'];
        $options[] = ['id' => 'North Carolina', 'name' => 'North Carolina'];
        $options[] = ['id' => 'North Dakota', 'name' => 'North Dakota'];
        $options[] = ['id' => 'Ohio', 'name' => 'Ohio'];
        $options[] = ['id' => 'Oklahoma', 'name' => 'Oklahoma'];
        $options[] = ['id' => 'Oregon', 'name' => 'Oregon'];
        $options[] = ['id' => 'Pennsylvania', 'name' => 'Pennsylvania'];
        $options[] = ['id' => 'Rhode Island', 'name' => 'Rhode Island'];
        $options[] = ['id' => 'South Carolina', 'name' => 'South Carolina'];
        $options[] = ['id' => 'South Dakota', 'name' => 'South Dakota'];
        $options[] = ['id' => 'Tennessee', 'name' => 'Tennessee'];
        $options[] = ['id' => 'Texas', 'name' => 'Texas'];
        $options[] = ['id' => 'Utah', 'name' => 'Utah'];
        $options[] = ['id' => 'Vermont', 'name' => 'Vermont'];
        $options[] = ['id' => 'Virginia', 'name' => 'Virginia'];
        $options[] = ['id' => 'Washington', 'name' => 'Washington'];
        $options[] = ['id' => 'West Virginia', 'name' => 'West Virginia'];
        $options[] = ['id' => 'Wisconsin', 'name' => 'Wisconsin'];
        $options[] = ['id' => 'Wyoming', 'name' => 'Wyoming'];
        $options[] = ['id' => 'Alberta', 'name' => 'Alberta'];
        $options[] = ['id' => 'British Columbia', 'name' => 'British Columbia'];
        $options[] = ['id' => 'Manitoba', 'name' => 'Manitoba'];
        $options[] = ['id' => 'New Brunswick', 'name' => 'New Brunswick'];
        $options[] = ['id' => 'Newfoundland and Labrador', 'name' => 'Newfoundland and Labrador'];
        $options[] = ['id' => 'Northwest Territories', 'name' => 'Northwest Territories'];
        $options[] = ['id' => 'Nova Scotia', 'name' => 'Nova Scotia'];
        $options[] = ['id' => 'Nunavut', 'name' => 'Nunavut'];
        $options[] = ['id' => 'Ontario', 'name' => 'Ontario'];
        $options[] = ['id' => 'Prince Edward Island', 'name' => 'Prince Edward Island'];
        $options[] = ['id' => 'Quebec', 'name' => 'Quebec'];
        $options[] = ['id' => 'Saskatchewan', 'name' => 'Saskatchewan'];
        $options[] = ['id' => 'Yukon Territory', 'name' => 'Yukon Territory'];
        $options[] = ['id' => 'Australian Capital Territory', 'name' => 'Australian Capital Territory'];
        $options[] = ['id' => 'New South Wales', 'name' => 'New South Wales'];
        $options[] = ['id' => 'Northern Territory', 'name' => 'Northern Territory'];
        $options[] = ['id' => 'Queensland', 'name' => 'Queensland'];
        $options[] = ['id' => 'South Australia', 'name' => 'South Australia'];
        $options[] = ['id' => 'Tasmania', 'name' => 'Tasmania'];
        $options[] = ['id' => 'Victoria', 'name' => 'Victoria'];
        $options[] = ['id' => 'Western Australia', 'name' => 'Western Australia'];
        $options[] = ['id' => 'Other', 'name' => 'Other'];
        return $options;
    }
}
