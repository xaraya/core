<?php
/**
 * Interfaces for dataobjects
 *
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/182.html
 */

interface iDataObject
{
    public function __construct(DataObjectDescriptor $descriptor);
    public function getItem(Array $data = array());
    public function checkInput(Array $data = array());
    public function showForm(Array $data = array());
    public function showDisplay(Array $data = array());
    public function getFieldValues(Array $data = array(), $bypass = 0);
    public function getDisplayValues(Array $data = array());
    public function createItem(Array $data = array());
    public function updateItem(Array $data = array());
    public function deleteItem(Array $data = array());
    public function getNextItemtype(Array $data = array());
}

interface iDataObjectList
{
    public function __construct(DataObjectDescriptor $descriptor);
    public function setArguments(Array $data = array());
    public function setSort($data);
    public function setWhere($data);
    public function setGroupBy($data);
    public function setCategories($data);
    public function &getItems(Array $data = array());
    public function countItems(Array $data = array());
    public function showView(Array $data = array());
    public function getViewOptions($itemid = null);
    public function &getViewValues(Array $data = array());
    public function getSortURL($currenturl=null);
    public function getNext(Array $data = array());
}
?>