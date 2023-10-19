<?php
/**
 * Interfaces for dataobjects
 *
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 */

interface iDataObject
{
    public function __construct(DataObjectDescriptor $descriptor);
    public function getItem(array $data = []);
    public function checkInput(array $data = []);
    public function showForm(array $data = []);
    public function showDisplay(array $data = []);
    public function getFieldValues(array $data = [], $bypass = 0);
    public function getDisplayValues(array $data = []);
    public function createItem(array $data = []);
    public function updateItem(array $data = []);
    public function deleteItem(array $data = []);
    public function getNextItemtype(array $data = []);
}

/**
 * Interfaces for dataobject lists
 *
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 */
interface iDataObjectList
{
    public function __construct(DataObjectDescriptor $descriptor);
    public function setArguments(array $data = []);
    public function setSort($data);
    public function setWhere($data);
    public function setGroupBy($data);
    public function setCategories($data);
    public function &getItems(array $data = []);
    public function countItems(array $data = []);
    public function showView(array $data = []);
    public function getViewOptions($itemid = null);
    public function &getViewValues(array $data = []);
    public function getSortURL($currenturl = null);
    /** @deprecated 2.2.0 relies on old datastore fields instead of object properties */
    public function getNext(array $data = []);
}
