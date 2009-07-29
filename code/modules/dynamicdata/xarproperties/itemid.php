<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Include the base class
 */
sys::import('modules.base.xarproperties.integerbox');

/**
 * Handle item id property
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
        $this->tplmodule = 'dynamic_data';
        $this->template = 'itemid';
        $this->filepath   = 'modules/dynamicdata/xarproperties';
    }
}

?>
