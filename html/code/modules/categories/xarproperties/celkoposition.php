<?php
/**
 * CelkoPosition Property
 *
 * @package modules\categories
 * @subpackage categories
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/147.html
 * @author Marc Lutolf <mfl@netspan.ch>
 */
sys::import('modules.dynamicdata.class.properties.base');

/**
 * The CelkoPosition property
 *
 * Show the position of an item in a tree of nested sets
 * The celko position of an item in a hierarchical structure is given by its position relative to another item.
 * Allowed positions are:
 * - before a given item on the same level
 * - after a given item on the same level
 * - first child of a given item
 * - last child of a given item
 *
 * The input for this property shows a dropdown of available categories, and a second dropdown with the values above
 * This code is used for categories, but it can (and is) used for any hierarchy of nested sets.
 *
 * Notes
 *
 * - This property is used in creating hierarchical data using the nested set model
 * - This property always has a reference to the parent object
 * - The top of the hierarchy is an entry with ID = 1, usually called "root"
 * - The default value for the celko table is xar_categories. Properties using other tables need to explicitly state the table.
 * - When exporting, we store itemid, parent_id, left_id, right_id in the value field of the property
 * - As a consequence, a non-empty $this-value when running the createValue method means we are in the process of importing from an XML file.
 * - When exporting we save an entire tree at a time
 * - Consequently when importing we import an entire tree. This tree is added as a new tree at the root level, alongside the trees already in the database.
 *   We therefore need to translate the itemid and parent_id values we are importing into the values of the new entries we are creating in the database,
 *   we also need to adjust all the left and right values. The latter adjustments are simply a matter of adding 2*n to the value of each left and right id, 
 *   where n is the number of entries already present in the table before the import.
 * - When creating values we have to distinguish between cases where the celkoposition property is using the same database table as other properties of the object in question,
 *   and where it is using a different table. Since the property does not define a source in the object's property configuration page, but rather defines its source table in its configuration page,
 *   this means that the property's createValue method takes care of creating the entry it needs, rather than the object's createItem method.
 *   In such cases createValue always runs AFTER createItem. This means that if the property is using the same table as other properties of the object, it will find a database entry has already 
 *   been created and only needs to update the values of that entry. This is the case of the categories object, which uses the same xar_categories table for all its properties.
 * - In contrast if the celkopposiion property uses a different source table than the object's other properties, it will (first) have to create an entry in that source table itself.
 *   We currently don't have any examples of such a case, but this might be the case of Chris' Uebertable :)
 *
 *   Filters must have (for now) the form P1.foo = 1 AND P1.bar = 2 etc.
 */
class CelkoPositionProperty extends DataProperty
{
    private $current_entry    = array();  // The current entry we are saving
    private $reference_entry  = array();  // The entry relative to which we define the position of this entry

    public $id           = 30074;
    public $name         = 'celkoposition';
    public $desc         = 'Celko Position';
    public $reqmodules   = array('categories');

    public $reference_id;                 // The ID of the item relative to which we define the position of this item
    public $include_reference = 1;        // Get a reference to the parent object
    public $moving;
    public $position          = 2;        // By default the position of this item is after the previous item
    public $rightorleft;                  // "left": this item will be the first child or previous sibling of the refenrence entry; "right": this will be the last child or next sibling of the reference entry
    public $inorout;                      // "in": this item will be a child of the reference entry; "out": this will be a sibling of the reference entry 

    public $catexists;
    public $itemindices      = array();    // helper variable to hold items when importing
    public $itemsknown       = array();    // helper variable to hold known references: oldkey => newkey
    public $itemsunresolved  = array();    // helper variable to hold unresolved references: newkey => oldkey
    public $offset           = 0;          // helper variable to hold offsets for left and right ids
    
    public $initialization_celkotable        = 'xar_categories';
    public $initialization_celkoname         = 'name';
    public $initialization_celkoparent_id    = 'parent_id';
    public $initialization_celkoleft_id      = 'left_id';
    public $initialization_celkoright_id     = 'right_id';
    public $initialization_celkofilter       = '';
    public $initialization_celkobasecategory = array(array('Celko Dropdown',array(array(1)),false,1));

    public $position_options = array();
    public $atomic_value     = array();    // The atomic values of this property are left, right and parent
    public $left;
    public $right;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'categories';
        $this->filepath  = 'modules/categories/xarproperties';

        $this->position_options = array(
					array('id' => '1', 'name' => xarMLS::translate('Right before, at the same level')),
					array('id' => '2', 'name' => xarMLS::translate('Right after, at the same level')),
					array('id' => '4', 'name' => xarMLS::translate('The first child item')),
					array('id' => '3', 'name' => xarMLS::translate('The last child item')),
					);
    }

	/**
	 * Get the value of a dropdown from a web page
	 * 
	 * @param  string name The name of the dropdown
	 * @param  string value The value of the dropdown
	 * @return bool|void   This method passes the value gotten to the validateValue method and returns its output.
	 */
    public function checkInput($name = '', $value = null)
    {
        if (!xarVar::fetch($name . '_reference_id', 'int:0', $reference_id)) return;
        if (!xarVar::fetch($name . '_position', 'enum:1:2:3:4', $position)) return;
        switch (intval($position)) {
            case 1: // before - same level
                $this->rightorleft = 'left';
                $this->inorout = 'out';
                break;
            case 2: // after - same level
                $this->rightorleft = 'right';
                $this->inorout = 'out';
                break;
            case 3: // last child item
                $this->rightorleft = 'right';
                $this->inorout = 'in';
                break;
            case 4: // first child item
                $this->rightorleft = 'left';
                $this->inorout = 'in';
                break;
            default: // any other value
                $this->rightorleft = 'right';
                $this->inorout = 'in';
                break;
        }

        // Avoid trying to go outside of the root entry
        if ($reference_id == 1) $this->inorout = 'in';
        
        $this->reference_id = $reference_id;
        return $this->validateValue($value);
    }

    public function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;

        // Obtain current information on the reference item
        $this->reference_entry = $this->getItem($this->reference_id);

        if ($this->reference_entry == false) {
            $this->invalid = xarMLS::translate('The reference entry does not exist');
            $this->value = null;
            return false;
        }

        // Obtain current information on the item
        $current_id = $this->objectref->properties[$this->objectref->primary]->value;
        $this->current_entry = $this->getItem($current_id);

        // Checking if the reference ID is of a child or itself
        if (!($this->current_entry == false) &&
           ($this->reference_entry[$this->initialization_celkoleft_id] >= $this->current_entry[$this->initialization_celkoleft_id])  &&
           ($this->reference_entry[$this->initialization_celkoleft_id] <= $this->current_entry[$this->initialization_celkoright_id])
          )
        {
            $this->invalid = xarMLS::translate('The reference entry cannot be the current entry or one of its children');
            $this->value = null;
            return false;
        }

        // No moving to before or after the root entry
        $isroot = $this->reference_entry[$this->initialization_celkoleft_id] == 1;
        if ($isroot && ($this->inorout == 'out')) {
            $this->invalid = xarMLS::translate('Cannot move an entry to before or after the root entry');
            $this->value = null;
            return false;
        }
        $this->setValue($value);
        return true;
    }

	/**
     * Create Value
     * 
     * @param int $itemid
     * @return boolean Returns true or false
     */
    public function createValue($itemid=0)
    {
        $n = $this->countItems($itemid);
        if ($n > 1) {
# --------------------------------------------------------
#
# There is more than one item for this itemid. That's a problem.
#
            throw new Exception(xarMLS::translate('More than one item for the itemid value #(1)',$itemid));
        } elseif ($n == 1) {
# --------------------------------------------------------
#
# There is one item for this itemid. This means it was already created by the object this property is bound to.
# Usually this means the same datasource is used for this property and the other properties of this object item (ex: categories table)
#
            if ($this->value) {
                // If we have a value, then we are creating an item from an imported XML file
                
                // If we are just starting an import, calculate the offset for new left and right links
                if (empty($this->itemsknown)) $this->offset = ($this->countItems() - 1) * 2;
                
                // Unpack the values of this property
                $params = unserialize($this->value);
                // Add this item to the list of known items for subsequent rounds
                $this->itemindices[$params[0]] = $params;
                $this->itemsknown[$params[0]] = $itemid;
                // Add this itemid to the list of items to be resolved
                $this->itemsunresolved[$itemid] = $params[0];
                
                sys::import('xaraya.structures.query');
                foreach ($this->itemsunresolved as $newkey => $oldkey) {
                    if (isset($this->itemindices[$oldkey])) {
                        $params = $this->itemindices[$oldkey];
                        $checkparent = (($params[1] == 0) || isset($this->itemsknown[$params[1]]));
                        if ($checkparent) {
                            // We have the parent reference: update the entry
                            $q = new Query('UPDATE', $this->initialization_celkotable);
                            if ($params[1] != 0) {
                                $q->addfield($this->initialization_celkoparent_id, $this->itemsknown[$params[1]]);
                            } else {
                                $q->addfield($this->initialization_celkoparent_id, $params[1]);
                            }
                            $q->addfield($this->initialization_celkoleft_id, $params[2] + $this->offset);
                            $q->addfield($this->initialization_celkoright_id, $params[3] + $this->offset);
                            $q->eq('id', $newkey);
                            $q->run();
    
                            // Remove this entry from the unresolveds
                            unset($this->itemsunresolved[$newkey]);
                        }
                    }
                }

            } else {
            	// Bound property with the item already created, no XML import: we need to update the celko fields

                if (empty($this->reference_id)) {
                    $point_of_insertion = 1;
                } else {
                    $parentItem = $this->getItem($this->reference_id);
               
                    $this->right = $parentItem[$this->initialization_celkoright_id];
                    $this->left = $parentItem[$this->initialization_celkoleft_id];                
                    /* Find out where you should put the new item in */
                    if (
                       !($point_of_insertion = $this->find_point_of_insertion($this->inorout, 
                                                                              $this->rightorleft, 
                                                                              (int)$this->left, 
                                                                              (int)$this->right))
                      )
                    {
                       return false;
                    }
                }
                
                if ($this->reference_id == 0) {
                    // This item is the first item to be created
                    $parent_id = $this->reference_id;            
                } elseif ($this->inorout == 'in') {
                    // This item is a child, so its parent is the reference item  
                    $parent_id = $this->reference_id;
                } else {
                    // This item is on he same level as the reference item; the parent is the same as that of the reference item
                    $parent_id = $parentItem[$this->initialization_celkoparent_id];
                }
                $this->updateposition($itemid, $parent_id, $point_of_insertion);
            }
        } else {
# --------------------------------------------------------
#
# There is no item for this itemid yet
# The datasource for this property is likely different from that of the other properties of this object.
# We'll need to create an item.
#
            if ($this->value) {
            // FIXME: this has not been tested!!!
                $this->unpackValue($this->value);
            } else {
# --------------------------------------------------------
#
# No value, this insert is via the UI rather than via import
# Obtain current information on the reference item
#
                $parentItem = $this->getItem($this->reference_id);
                
                if ($parentItem == false) {
                   xarSession::setVar('errormsg', xarMLS::translate('The parent item does not exist'));
                   return false;
                }
                $this->right = $parentItem[$this->initialization_celkoright_id];
                $this->left = $parentItem[$this->initialization_celkoleft_id];                
            }
            
            /* Find out where you should put the new item in */
            if (
               !($point_of_insertion = $this->find_point_of_insertion($this->inorout, 
                                                                      $this->rightorleft, 
                                                                      $this->left, 
                                                                      $this->right))
              )
            {
               return false;
            }

            /* Find the right parent for this item */
            if (strtolower($this->inorout) == 'in') {
                $parent_id = (int)$this->reference_id;
            } else {
                $parent_id = (int)$parentItem[$this->initialization_celkoparent_id];
            }
            $this->updateposition($itemid, $parent_id, $point_of_insertion);
        }
        return true;
    }

	/**
     * Updates value for the given item id.
	 *
     * @param int $itemid ID of the item to be updated
     * @return boolean|void Returns true on success, false on failure
     */
    public function updateValue($itemid=0)
    {
        // Sanity checks: this property may not need to be updated
        if (empty($this->reference_entry) || empty($this->current_entry)) return true;
        
        // Check the current item
        $current_entry = $this->getItem($itemid);

        if ($current_entry == false) {
            xarSession::setVar('errormsg', xarMLS::translate('The entry you are updating does not exist'));
            return false;
        }

       // Find the needed variables for moving things...
       $point_of_insertion =
                   $this->find_point_of_insertion($this->inorout, 
                                                  $this->rightorleft, 
                                                  $this->reference_entry[$this->initialization_celkoleft_id], 
                                                  $this->reference_entry[$this->initialization_celkoright_id]);
       $size = $this->current_entry[$this->initialization_celkoright_id] - $this->current_entry[$this->initialization_celkoleft_id] + 1;
       $distance = $point_of_insertion - $this->current_entry[$this->initialization_celkoleft_id];

       // If necessary to move then evaluate
       if ($distance != 0) { // It´s Moving, baby!  Do the Evolution!
          if ($distance > 0)
          { // moving forward
              $distance = $point_of_insertion - $this->current_entry[$this->initialization_celkoright_id] - 1;
              $deslocation_outside = -$size;
              $between_string = ($this->current_entry[$this->initialization_celkoright_id] + 1)." AND ".($point_of_insertion - 1);
          }
          else
          { // $distance < 0 (moving backward)
              $deslocation_outside = $size;
              $between_string = $point_of_insertion." AND ".($this->current_entry[$this->initialization_celkoleft_id] - 1);
          }

          // TODO: besides portability, also check performance here
          $SQLquery = "UPDATE " . $this->initialization_celkotable . " SET
                       " . $this->initialization_celkoleft_id . " = CASE
                        WHEN " . $this->initialization_celkoleft_id . " BETWEEN " . $this->current_entry[$this->initialization_celkoleft_id] . " AND " . $this->current_entry[$this->initialization_celkoright_id] . "
                           THEN " . $this->initialization_celkoleft_id . " + ($distance)
                        WHEN " . $this->initialization_celkoleft_id . " BETWEEN $between_string
                           THEN " . $this->initialization_celkoleft_id . " + ($deslocation_outside)
                        ELSE " . $this->initialization_celkoleft_id . "
                        END,
                      " . $this->initialization_celkoright_id . " = CASE
                        WHEN " . $this->initialization_celkoright_id . " BETWEEN " . $this->current_entry[$this->initialization_celkoleft_id] . " AND " . $this->current_entry[$this->initialization_celkoright_id] . "
                           THEN " . $this->initialization_celkoright_id . " + ($distance)
                        WHEN " . $this->initialization_celkoright_id . " BETWEEN $between_string
                           THEN " . $this->initialization_celkoright_id . " + ($deslocation_outside)
                        ELSE " . $this->initialization_celkoright_id . "
                        END
                     ";
                     // This seems SQL-92 standard... Its a good test to see if
                     // the databases we are supporting are complying with it. This can be
                     // broken down in 3 simple UPDATES which shouldnt be a problem with any database

            $dbconn = xarDB::getConn();
            $result = $dbconn->Execute($SQLquery);
            if (!$result) return;

            /* Find the right parent for this item */
            if (strtolower($this->inorout) == 'in') {
                $parent_id = $this->reference_id;
            } else {
                $parent_id = $this->reference_entry[$this->initialization_celkoparent_id];
            }
            // Update parent id
            $SQLquery = "UPDATE " . $this->initialization_celkotable .
                         " SET " . $this->initialization_celkoparent_id . " = ?
                       WHERE id = ?";
            $result = $dbconn->Execute($SQLquery,array($parent_id, $itemid));
            if (!$result) return;
        } 
    }

	/**
	 * Display a dropdown for input
	 * 
	 * @param  array data An array of input parameters
	 * @return string     HTML markup to display the property for input on a web page
	 */
    public function showInput(Array $data = array())
    {
        if (!isset($data['position_options'])) $data['position_options'] = $this->position_options;
        if (!isset($data['position'])) $data['position'] = $this->position;
        if (!isset($data['reference_id'])) $data['reference_id'] = $this->reference_id;
        if (isset($data['filter'])) $this->initialization_celkofilter = $data['filter'];
        if (isset($data['base_category'])) $this->initialization_celkobasecategory = $data['base_category'];
        
        $include_self = $this->initialization_celkobasecategory[0][2];
        $data['itemid'] = isset($data['itemid']) ? $data['itemid'] : $this->_itemid;
        if (!empty($data['itemid'])) {        
            $data['item'] = $this->getItem($data['itemid']);
            $items = $this->getItems(array('cid' => $include_self,
                                           'eid' => $data['itemid']));
            $data['id'] = $data['itemid'];
        } else {
            $data['item'] = Array('left_id'=>0,'right_id'=>0,'name'=>'','description'=>'', 'template' => '');
            $items = $this->getItems(array('cid' => $include_self));
            $data['id'] = null;
        }

        $item_Stack = array ();

        foreach ($items as $key => $item) {
            $items[$key]['slash_separated'] = '';

            while ((count($item_Stack) > 0 ) &&
                   ($item_Stack[count($item_Stack)-1]['indentation'] >= $item['indentation'])
                  ) {
                array_pop($item_Stack);
            }

            foreach ($item_Stack as $stack_cat) {
                $items[$key]['slash_separated'] .= $stack_cat[$this->initialization_celkoname].'&#160;/&#160;';
            }

            array_push($item_Stack, $item);
            $items[$key]['slash_separated'] .= $item[$this->initialization_celkoname];
        }

        $data['items'] = $items;
        
        // Let the template know whether this is a new or existing object
        $data['isnew'] = empty($this->objectref->properties['id']->value);

        // If the current item has no reference item, then find the last such item and make this one the next in line
        if ($data['reference_id'] == 0 && !empty($items)) {
            $right = array();
            foreach ($items as $key => $row) {
                $right[$key]  = $row['right'];
            }
            sort($right);
            $rightkeys = array_keys($right);
            $topkey = array_pop($rightkeys);
            $data['reference_id'] = (int)$items[$topkey]['id'];
        }
        
        // Add position names for use in the template
        $data['left_id'] = $this->initialization_celkoleft_id;
        $data['right_id'] = $this->initialization_celkoright_id;
        $data['parent_id'] = $this->initialization_celkoparent_id;
        
        return parent::showInput($data);

    }
    
	/**
	 * Used to show the hidden data
	 * 
	 * @param  array data An array of input parameters
	 * @return string   Returns true or false 
	 */
    public function showHidden(Array $data = array())
    {
        if (!isset($data['position_options'])) $data['position_options'] = $this->position_options;
        if (!isset($data['position'])) $data['position'] = $this->position;
        if (!isset($data['reference_id'])) $data['reference_id'] = $this->reference_id;
        if (isset($data['filter'])) $this->initialization_celkofilter = $data['filter'];
        
        $data['itemid'] = isset($data['itemid']) ? $data['itemid'] : $this->_itemid;
        if (!empty($data['itemid'])) {        
            $data['item'] = $this->getItem($data['itemid']);
            $items = $this->getItems(array('cid' => false,
                                           'eid' => $data['itemid']));
            $data['id'] = $data['itemid'];
        } else {
            $data['item'] = Array('left_id'=>0,'right_id'=>0,'name'=>'','description'=>'', 'template' => '');
            $items = $this->getItems(array('cid' => false));
            $data['id'] = null;
        }
        $data['items'] = $items;
        
        // Let the template know whether this is a new or existing object
        $data['isnew'] = empty($this->objectref->properties['id']->value);

        // If the current item has no reference item, then find the last such item and make this one the next in line
        if ($data['reference_id'] == 0 && !empty($items)) {
            $right = array();
            foreach ($items as $key => $row) {
                $right[$key]  = $row['right'];
            }
            sort($right);
            $rightkeys = array_keys($right);
            $topkey = array_pop($rightkeys);
            $data['reference_id'] = (int)$items[$topkey]['id'];
        }
        
        return parent::showHidden($data);

    }
    
    // Update the parent, item, left and right IDs at the point of insertion
    // while moving all the links to the left and right apart to make place for the insertion
    function updateposition($itemid=0, $parent=0, $point_of_insertion=1) 
    {
        $bindvars = array();
        $bindvars[1] = array();
        $bindvars[2] = array();
        $bindvars[3] = array();

        /* Opening space for the new node */
        $SQLquery[1] = "UPDATE " . $this->initialization_celkotable .
                        " SET " . $this->initialization_celkoright_id . " = " . $this->initialization_celkoright_id . " + 2
                        WHERE " . $this->initialization_celkoright_id . ">= ?";
        $bindvars[1][] = $point_of_insertion;

        $SQLquery[2] = "UPDATE " . $this->initialization_celkotable .
                        " SET " . $this->initialization_celkoleft_id . " = " . $this->initialization_celkoleft_id . " + 2
                        WHERE " . $this->initialization_celkoleft_id . ">= ?";
        $bindvars[2][] = $point_of_insertion;
        // Both can be transformed into just one SQL-statement, but I dont know if every database is SQL-92 compliant(?)

        $SQLquery[3] = "UPDATE " . $this->initialization_celkotable . " SET " .
                                    $this->initialization_celkoparent_id . " = ?," .
                                    $this->initialization_celkoleft_id . " = ?," .
                                    $this->initialization_celkoright_id . " = ?
                                     WHERE id = ?";
        $bindvars[3] = array($parent, $point_of_insertion, $point_of_insertion + 1,$itemid);

        $dbconn = xarDB::getConn();
        for ($i=1;$i<4;$i++) if (!$dbconn->Execute($SQLquery[$i],$bindvars[$i])) return;
    }

	/**
     * Fetch item from the database
     * 
     * @param int $id ID of the item
     * @return array|void Array of fetched item
     */
    public function getItem($id) 
    {
        sys::import('xaraya.structures.query');
        $q = new Query('SELECT', $this->initialization_celkotable);
        $q->eq('id',$id);
        if (!$q->run()) return;
        $result = $q->row();
        return $result;
    }
    
	/**
     * Get the value of this property for a particular item
     *
     * @param int $id the id we want the value for
     * @return string return serialized value of $id param
     */
    public function getItemValue($id) 
    {
        return serialize($this->getItem($id));
    }
     
	/*
	 * Move the item from one position to other in hierarchical structure of categories
	 *
	 * @param int $id ID to be moved
     */
    public function mountValue($id) 
    {
        $result = $this->getItem($id);
        if (empty($result)) return $result;
        $this->atomic_value['left'] = $result[$this->initialization_celkoleft_id];
        $this->atomic_value['right'] = $result[$this->initialization_celkoright_id];
        $this->atomic_value['parent'] = $result[$this->initialization_celkoparent_id];
    }
    
	/**
	 * Return the number of items in the celko table that have this itemid
	 */
    private function countItems($itemid=0)
    {
        $sql = "SELECT COUNT(id) AS childnum
                  FROM " . $this->initialization_celkotable;
        if (!empty($itemid)) {
            $sql .= " WHERE id = " . $itemid;
        }
        $dbconn = xarDB::getConn();
        $result = $dbconn->Execute($sql);
        if (!$result) return;
        $num = $result->fields[0];
        $result->Close();
        return $num;
    }

	/*
	 * Used to build a tree of categories
	 *
	 * @param int $parent_id Parent ID of the tree
	 * @param int $left_id Left ID of the first level categories
	 * @return int returns the right value of node
	*/
    function build_tree($parent_id, $left_id=1)
    {       
        // We need the left ID in case there are other top level categories, and we need to know where this tree starts
        // the right value of this node is the left value + 1  
        $right_id = $left_id+1;  
    
        // Get all children of this node
        sys::import('modules.categories.class.worker');
        $worker = new CategoryWorker();
        $worker->setTable($this->initialization_celkotable);
        $result = $worker->getchildren($parent_id);
    
        foreach ($result as $child) {
           // recursive execution of this function for each  
           // child of this node  
           // $right_id is the current right value, which is  
           // incremented by the rebuild_tree function  
           $right_id = $this->build_tree($child['id'], $right_id);  
       }  
       // we've got the left value, and now that we've processed  
       // the children of this node we also know the right value  
        $q = new Query('UPDATE', $this->initialization_celkotable);
        $q->addfield($this->initialization_celkoleft_id, $left_id);
        $q->addfield($this->initialization_celkoright_id, $right_id);
        $q->run();
     
       // return the right value of this node + 1  
       return $right_id+1;  
    }  

    // Given a left and right link id, define a point of insertion to the left or right of either
    private function find_point_of_insertion($inorout, $rightorleft, $left, $right)
    {
        $rightorleft = strtolower ($rightorleft);
        $inorout = strtolower ($inorout);

        switch($rightorleft) {
            case "right":
               $point_of_insertion = $right;

               switch($inorout) {
                  case "out":
                     $point_of_insertion++;
                  break;

                  case "in":
                  break;

                  default:
                    $msg = xarMLS::translate('Valid values: IN or OUT');
                    throw new BadParameterException(null, $msg);
               }
            break;
            case "left":
               $point_of_insertion = $left;
               switch($inorout) {
                  case "out":
                  break;

                  case "in":
                     $point_of_insertion++;
                  break;

                  default:
                    $msg = xarMLS::translate('Valid values: IN or OUT');
                    throw new BadParameterException(null, $msg);
               }
            break;
            default:
            $msg = xarMLS::translate('Valid values: RIGHT or LEFT');
            throw new BadParameterException(null, $msg);
        }
        return $point_of_insertion;
    }

    // Get all the items of this Celko tree
    private function getItems($args)
    {
        extract($args);

        $indexby = 'default';

        // If the name has more than one field, get all its fields as an array
        $nameparts = explode(',', $this->initialization_celkoname);
        $select_fields = '';
        foreach ($nameparts as $part) {
            $select_fields .= "P1." . $part . ",";
        }
        /*
            The first WHERE conditions select for each category from P1, the categories in P2 that contain it (i.e. the parents).
            These are reurned as COUNT(P1...)
            The second WHERE conditions below selects all categories in P1 except the current category and its descendents.
        */
        $bindvars = array();
        $SQLquery = "SELECT COUNT(P2.id) AS indent,
                            P1.id,"
                            . $select_fields .
                            "P1." . $this->initialization_celkoparent_id . ",
                            P1." . $this->initialization_celkoleft_id . ",
                            P1." . $this->initialization_celkoright_id . 
                       " FROM " . $this->initialization_celkotable . " P1, " .
                            $this->initialization_celkotable . " P2
                      WHERE P1." . $this->initialization_celkoleft_id . " 
                         >= P2." . $this->initialization_celkoleft_id . " 
                        AND P1." . $this->initialization_celkoleft_id . " 
                         <= P2." . $this->initialization_celkoright_id;

        if (isset($eid) && !is_array($eid) && $eid != false) {
           $ecat = $this->getItem($eid);
           if ($ecat == false) {
               xarSession::setVar('errormsg', xarMLS::translate('That item does not exist'));
               return array();
           }
           //$SQLquery .= " AND P1.left_id
           //               NOT BETWEEN ? AND ? ";
           $SQLquery .= " AND (P1." . $this->initialization_celkoleft_id . " < ? OR P1." . $this->initialization_celkoleft_id . " > ?)";
           $bindvars[] = $ecat[$this->initialization_celkoleft_id]; $bindvars[] = $ecat[$this->initialization_celkoright_id];
        }

        // Add any SQL conditions passed from the template or initialization here
        if (!empty($this->initialization_celkofilter))
            $SQLquery .= " AND " . $this->initialization_celkofilter;
        
        // Have to specify all selected attributes in GROUP BY
        // CHECKME: this might be DB specific
        $SQLquery .= " GROUP BY P1.id, " . $select_fields . " P1." . $this->initialization_celkoparent_id . ", P1." . $this->initialization_celkoleft_id . ", P1." . $this->initialization_celkoright_id . " ";
        $SQLquery .= " ORDER BY P1." . $this->initialization_celkoleft_id;

    // cfr. xarcachemanager - this approach might change later
        $expire = xarModVars::get('categories','cache.userapi.getcat');
        $dbconn = xarDB::getConn();
        if (!empty($expire)){
            $result = $dbconn->CacheExecute($expire,$SQLquery,$bindvars);
        } else {
            $result = $dbconn->Execute($SQLquery, $bindvars);
        }
        if (!$result) return;
        if ($result->EOF) return Array();

        $items = array();

        // FIXME: in PDO the last row appears twice ein the resulting array
        $index = -1;
        $result->first();
        while (!$result->EOF) {
            list($indentation,
                    $id,
                    $name,
                    $parent,
                    $left,
                    $right
                   ) = $result->fields;
            $result->next();

            if ($indexby == 'cid') {
                $index = $id;
            } else {
                $index++;
            }

            // are we looking to have the output in the "standard" form?
            if (!empty($dropdown)) {
                $items[$index+1] = Array(
                    'id'         => $id,
                    'name'        => $name,
                );
            } else {
                $items[$index] = Array(
                    'indentation' => $indentation,
                    'id'          => $id,
                    'name'        => $name,
                    'parent'      => $parent,
                    'left'        => $left,
                    'right'       => $right,
                );
            }
        }
        $result->Close();

        if (!empty($dropdown)) {
            $items[0] = array('id' => 0, 'name' => '');
        }
        return $items;
    }

/**
 * Unpack the value of this property (imported from an XML file)
 *
 * Takes the serialized value in $this->value and assigns its unserialized values to their proper places
 */
    // Itemid is the id of the row created. We assume same table for all properties of the object
    private function unpackValue($itemid)
    {
        try {
            // Unpack the values of this property
            $params = unserialize($this->value);
            // Add this item to the list of known items for subsequent rounds
            $this->itemindices[$params[0]] = $params;
            $this->itemsknown[$params[0]] = $itemid;
            // Add this itemid to the list of items to be resolved
            $this->itemsunresolved[$itemid] = $params[0];
            return true;
            
            // Get the value for the reference ID (parent)
//            $parent_id = $params[1];
        } catch (Exception $e) {
//            $parent_id = 0;
//            $params[0] = $itemid;
        }
        
        $this->setCelkoValues($itemid, $params[0]);
                
        return $parent_id;
    }
    
	/**
	 * Set the values of this property
	 */
    private function setCelkoValues($newid, $oldid)
    {
        if (isset($this->itemindices[$newid])) {
            $params = $this->itemindices[$newid];
            $oldid = $params[0];
        } else {
            // We'll still need to resolve this entry later
            // add this parent to the list of known parents for subsequent rounds
            $this->itemindices[$newid] = null;
            $this->itemsunresolved[$newid] = $oldid;
            $parent_id = 0;
        }
        
        // Set the left and right values null and let the updateposition method take care of them
        $this->right = null;
        $this->left = null;
        return true;
    }
    
	/**
	 * The import value imports the value from an XML file.
	 */
    public function importValue(SimpleXMLElement $element)
    {
        return $element->{$this->name};
    }

	/**
	 * The export value is a serialized array with the elements itemid, parentid, leftid, rightid
	 */
    public function exportValue($itemid, $item)
    {
        $thisItem = $this->getItem($itemid);
        if (empty($thisItem)) return serialize(array($itemid,0,0,0));
        $exportvalue = serialize(array((int)$itemid, (int)$thisItem[$this->initialization_celkoparent_id], (int)$thisItem[$this->initialization_celkoleft_id], (int)$thisItem[$this->initialization_celkoright_id]));
        return $exportvalue;
    }

	/**
     * Update the current configuration rule in a specific way for this property type
     *
     * @param  array data An array of input parameters
     */
    public function updateConfiguration(Array $data = array())
    {
        // Removes the empty line for adding a row
        array_pop($data['configuration']['initialization_celkobasecategory']);
        
        // Ignore/remove any empty rows, i.e. those where there is no title
        foreach ($data['configuration']['initialization_celkobasecategory'] as $row => $columns) {
            if (empty($columns[0])) unset($data['configuration']['initialization_celkobasecategory'][$row]);
        }
        return parent::updateConfiguration($data);
    }
}

sys::import('modules.dynamicdata.class.properties.interfaces');

class CelkoPositionPropertyInstall extends CelkoPositionProperty implements iDataPropertyInstall
{
	/**
     * Install method
     * 
     * @param array $data Parameter data array
     * @return boolean Returns true.
     */
    public function install(Array $data=array())
    {
        $dat_file = sys::code() . 'modules/categories/xardata/celkoposition_configurations-dat.xml';
        $data = array('file' => $dat_file);
        try {
            $objectid = xarMod::apiFunc('dynamicdata','util','import', $data);
        } catch (Exception $e) {
            //
        }
        return true;
    }
}
