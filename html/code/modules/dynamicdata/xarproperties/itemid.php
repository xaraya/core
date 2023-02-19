<?php
/**
 * Include the base class
 */
sys::import('modules.base.xarproperties.integerbox');

/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * This property models an ID
 * This is a speciic case of a number. It is sually used to hold datarecord IDs, which are assigned, unique and autoincrement
 */
class ItemIDProperty extends NumberBoxProperty
{
    public $id         = 21;
    public $name       = 'itemid';
    public $desc       = 'Item ID';
    public $reqmodules = array('dynamicdata');

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'dynamicdata';
        $this->template = 'itemid';
        $this->filepath   = 'modules/dynamicdata/xarproperties';
        
        $this->defaultvalue = null;
    }
}
