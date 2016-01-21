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
 * Handle the static text property
 */
class StaticTextProperty extends DataProperty
{
    public $id         = 1;
    public $name       = 'static';
    public $desc       = 'Static Text';
    public $reqmodules = array('base');

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'base';
        $this->template = 'static';
        $this->filepath = 'modules/base/xarproperties';
    }

    public function validateValue($value = null)
    {
        xarLog::message("DataProperty::validateValue: Validating property " . $this->name);
        if (isset($value) && $value != $this->value) {
            $this->invalid = xarML('static text: #(1)', $this->name);
            xarLog::message($this->invalid, XARLOG_LEVEL_ERROR);
            $this->value = null;
            return false;
        }
        return true;
    }
}
?>