<?php
/**
 * @package modules
 * @subpackage base module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/68.html
 *
 * @author John Cox
 */
/**
 * Include the base class
 */
sys::import('modules.base.xarproperties.dropdown');
/**
 * Handle the country list property
 */
class CountryListProperty extends SelectProperty
{
    public $id         = 42;
    public $name       = 'countrylisting';
    public $desc       = 'Country Dropdown';

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->template  = 'countrylisting';
    }

}
?>