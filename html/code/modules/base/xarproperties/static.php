<?php

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
 * This property displays a chunk of static text. The text cannot be modified.
 */
class StaticTextProperty extends DataProperty
{
    public $id         = 1;
    public $name       = 'static';
    public $desc       = 'Static Text';
    public $reqmodules = ['base'];

    public function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'base';
        $this->template = 'static';
        $this->filepath = 'modules/base/xarproperties';
    }
    /**
 * Validate the value of a input
 *
 * @return bool Returns true if the value passes all validation checks; otherwise returns false.
 */

    public function validateValue($value = null)
    {
        $this->log()->debug("DataProperty::validateValue: Validating property " . $this->name);

        if (isset($value) && $value != $this->value) {
            $this->invalid = $this->ml('static text: #(1)', $this->name);
            $this->log()->error($this->invalid);
            $this->value = null;
            return false;
        }
        return true;
    }
}
