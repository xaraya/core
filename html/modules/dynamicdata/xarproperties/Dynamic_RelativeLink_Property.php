<?php
/**
 * Dynamic Relative Link property Property
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 * @author Marc Luotlf <mfl@netspan.ch>
 */

/**
 * Include the parent class
 *
 */
sys::import('modules.dynamicdata.xarproperties.Dynamic_ItemID_Property');

/**
 * handle relative link property
 *
 * @package dynamicdata
 */
class Dynamic_RelativeLink_Property extends Dynamic_ItemID_Property
{
    public $id         = 30049;
    public $name       = 'relativelink';
    public $desc       = 'Relative Link';
    public $reqmodules = array('dynamicdata');
}

?>
