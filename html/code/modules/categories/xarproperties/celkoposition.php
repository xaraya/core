<?php
/**
 * CelkoPosition Property
 *
 * @package properties\celkoposition
 * @category Third Party Xaraya Property
 * @version 1.0.0
 * @copyright (C) 2011 Netspan AG
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @author Marc Lutolf <mfl@netspan.ch>
 */
sys::import('modules.dynamicdata.class.properties.base');

/**
 * Handle the CelkoPosition property
 *
 * Show the position of an item in a tree of nested sets
 *
 * Notes
 *
 * - This property is used in creating hierarchical data using the nested set model
 * - This property always has a reference to the parent object
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
 */
class CelkoPositionProperty extends DataProperty
{
    public $id           = 30074;
    public $name         = 'celkoposition';
    public $desc         = 'Celko Position';
    public $reqmodules   = array('categories');

    public $reference_id      = 0;          // The ID of the item relative to which we define the position of this item
    public $include_reference = 1;          // Get a reference to the parent object
    public $moving;
    public $position          = 2;          // By default the position of this item is after the previous item
    public $rightorleft;
    public $inorout;

    public $catexists;
    public $dbconn;
    public $itemindices     = array();    // helper variable to hold items when importing
    public $itemsknown      = array();    // helper variable to hold known references: oldkey => newkey
    public $itemsunresolved = array();    // helper variable to hold unresolved references: newkey => oldkey
    public $offset          = 0;          // helper variable to hold offsets for left and right ids
    
    public $initialization_celkotable     = 'xar_categories';
    public $initialization_celkoname      = 'name';
    public $initialization_celkoparent_id = 'parent_id';
    public $initialization_celkoleft_id   = 'left_id';
    public $initialization_celkoright_id  = 'right_id';

    public $atomic_value    = array();    // The atomic calues of this property are lrft, right and parent

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'categories';
        $this->filepath  = 'modules/categories/xarproperties';
        $this->dbconn = xarDB::getConn();
    }

    public function checkInput($name = '', $value = null)
    {
        if (!xarVarFetch($name . '_reference_id', 'int:0', $reference_id)) return;
        if (!xarVarFetch($name . '_position', 'enum:1:2:3:4', $position)) return;
        switch (intval($position)) {
            case 1: // before - same level
            default:
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
        }
        $this->reference_id = $reference_id;
        return $this->validateValue($value);
    }

    public function createValue($itemid=0)
    {
        $n = $this->countItems($itemid);
        if ($n > 1) {
# --------------------------------------------------------
#
# There is more than one item for this itemid. That's a problem.
#
            throw new Exception(xarML('More than one item for the itemid value #(1)',$itemid));
        } elseif ($n == 1) {
# --------------------------------------------------------
#
# There is one item for this itemid. This means it was already created
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
                if (empty($this->reference_id)) {
                    $point_of_insertion = 1;
                } else {
                    $parentItem = $this->getItem($this->reference_id);
               
                    $this->right = $parentItem['right_id'];
                    $this->left = $parentItem['left_id'];                
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
                }
                
                if ($this->reference_id == 0) {
                    // This item is the first item to be created
                    $parent_id = $this->reference_id;            
                } elseif ($this->inorout == 'in') {
                    // This item is a child, so its parent is the reference item  
                    $parent_id = $this->reference_id;
                } else {
                    // This item is on he same level as the reference item; the parent is the same as that of the reference item
                    $parent_id = $parentItem['parent_id'];
                }
                $itemid = $this->updateposition($itemid, $parent_id, $point_of_insertion);
                $this->updateValue($itemid);
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
                $this->unpackValue();
            } else {
# --------------------------------------------------------
#
# No value, this insert is via the UI rather than via import
# Obtain current information on the reference item
#
                $parentItem = $this->getItem($this->reference_id);
                
                if ($parentItem == false) {
                   xarSession::setVar('errormsg', xarML('The parent item does not exist'));
                   return false;
                }
                $this->right = $parentItem['right_id'];
                $this->left = $parentItem['left_id'];                
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
                $parent_id = (int)$parentItem['parent_id'];
            }
            $itemid = $this->updateposition($itemid,$parent_id,$point_of_insertion);
        }
        return true;
    }

    public function updateValue($itemid=0)
    {
        // Obtain current information on the item
        $thisItem = $this->getItem($itemid);

        if ($thisItem == false) {
           xarSession::setVar('errormsg', xarML('That item does not exist'));
           return false;
        }

       // Obtain current information on the reference item
       $refcat = $this->getItem($this->reference_id);

       if ($refcat == false) {
           xarSession::setVar('errormsg', xarML('That item does not exist'));
           return false;
       }

       // Checking if the reference ID is of a child or itself
       if (
           ($refcat['left_id'] >= $thisItem['left_id'])  &&
           ($refcat['left_id'] <= $thisItem['right_id'])
          )
       {
            $msg = xarML('This item references siblings.');
            throw new BadParameterException(null, $msg);
       }

       // Find the needed variables for moving things...
       $point_of_insertion =
                   $this->find_point_of_insertion($this->inorout, 
                                                  $this->rightorleft, 
                                                  $refcat['left_id'], 
                                                  $refcat['right_id']);
       $size = $thisItem['right_id'] - $thisItem['left_id'] + 1;
       $distance = $point_of_insertion - $thisItem['left_id'];

       // If necessary to move then evaluate
       if ($distance != 0) { // It´s Moving, baby!  Do the Evolution!
          if ($distance > 0)
          { // moving forward
              $distance = $point_of_insertion - $thisItem['right_id'] - 1;
              $deslocation_outside = -$size;
              $between_string = ($thisItem['right_id'] + 1)." AND ".($point_of_insertion - 1);
          }
          else
          { // $distance < 0 (moving backward)
              $deslocation_outside = $size;
              $between_string = $point_of_insertion." AND ".($thisItem['left_id'] - 1);
          }

          // TODO: besided portability, also check performance here
          $SQLquery = "UPDATE " . $this->initialization_celkotable . " SET
                       " . $this->initialization_celkoleft_id . " = CASE
                        WHEN " . $this->initialization_celkoright_id . " BETWEEN ".$thisItem['left_id']." AND ".$thisItem['right_id']."
                           THEN " . $this->initialization_celkoleft_id . " + ($distance)
                        WHEN " . $this->initialization_celkoleft_id . " BETWEEN $between_string
                           THEN " . $this->initialization_celkoleft_id . " + ($deslocation_outside)
                        ELSE " . $this->initialization_celkoleft_id . "
                        END,
                      " . $this->initialization_celkoright_id . " = CASE
                        WHEN " . $this->initialization_celkoright_id . " BETWEEN ".$thisItem['left_id']." AND ".$thisItem['right_id']."
                           THEN " . $this->initialization_celkoright_id . " + ($distance)
                        WHEN " . $this->initialization_celkoright_id . " BETWEEN $between_string
                           THEN " . $this->initialization_celkoright_id . " + ($deslocation_outside)
                        ELSE " . $this->initialization_celkoright_id . "
                        END
                     ";
                     // This seems SQL-92 standard... Its a good test to see if
                     // the databases we are supporting are complying with it. This can be
                     // broken down in 3 simple UPDATES which shouldnt be a problem with any database

            $result = $this->dbconn->Execute($SQLquery);
            if (!$result) return;

          /* Find the right parent for this item */
          if (strtolower($this->inorout) == 'in') {
              $parent_id = $this->reference_id;
          } else {
              $parent_id = $refcat['parent_id'];
          }
          // Update parent id
          $SQLquery = "UPDATE " . $this->initialization_celkotable .
                       " SET " . $this->initialization_celkoparent_id . " = ?
                       WHERE id = ?";
        $result = $this->dbconn->Execute($SQLquery,array($parent_id, $itemid));
        if (!$result) return;

       } 
    }

    public function showInput(Array $data = array())
    {
        if (!isset($data['position'])) $data['position'] = $this->position;
        if (!isset($data['reference_id'])) $data['reference_id'] = $this->reference_id;
        
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

        $item_Stack = array ();

        foreach ($items as $key => $item) {
            $items[$key]['slash_separated'] = '';

            while ((count($item_Stack) > 0 ) &&
                   ($item_Stack[count($item_Stack)-1]['indentation'] >= $item['indentation'])
                  ) {
                array_pop($item_Stack);
            }

            foreach ($item_Stack as $stack_cat) {
                $items[$key]['slash_separated'] .= $stack_cat['name'].'&#160;/&#160;';
            }

            array_push($item_Stack, $item);
            $items[$key]['slash_separated'] .= $item['name'];
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
        
        return parent::showInput($data);

    }
    
    public function showHidden(Array $data = array())
    {
        if (!isset($data['position'])) $data['position'] = $this->position;
        if (!isset($data['reference_id'])) $data['reference_id'] = $this->reference_id;
        
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
        // Both can be transformed into just one SQL-statement, but i dont know if every database is SQL-92 compliant(?)

        $SQLquery[3] = "UPDATE " . $this->initialization_celkotable . " SET " .
                                    $this->initialization_celkoparent_id . " = ?," .
                                    $this->initialization_celkoleft_id . " = ?," .
                                    $this->initialization_celkoright_id . " = ?
                                     WHERE id = ?";
        $bindvars[3] = array($parent, $point_of_insertion, $point_of_insertion + 1,$itemid);

        for ($i=1;$i<4;$i++) if (!$this->dbconn->Execute($SQLquery[$i],$bindvars[$i])) return;
    }

    public function getItem($id) 
    {
        sys::import('xaraya.structures.query');
        $q = new Query('SELECT', $this->initialization_celkotable);
        $q->eq('id',$id);
        if (!$q->run()) return;
        $result = $q->row();
        if (empty($result)) return $result;
        $result['name'] = $result[$this->initialization_celkoname];
        $result['parent_id'] = $result[$this->initialization_celkoparent_id];
        $result['left_id'] = $result[$this->initialization_celkoleft_id];
        $result['right_id'] = $result[$this->initialization_celkoright_id];
        return $result;
    }
    
    public function getItemValue($id) 
    {
        return serialize($this->getItem($id));
    }
    
    public function mountValue($id) 
    {
        $result = $this->getItem($id);
        if (empty($result)) return $result;
        $this->atomic_value['left'] = $result['left_id'];
        $this->atomic_value['right'] = $result['right_id'];
        $this->atomic_value['parent'] = $result['parent_id'];
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
        $result = $this->dbconn->Execute($sql);
        if (!$result) return;
        $num = $result->fields[0];
        $result->Close();
        return $num;
    }

    function build_tree($parent_id, $left_id=1)
    {       
        // We need tohe left ID in case there are other top level categories, and we need to know where this tree starts
        // the right value of this node is the left value + 1  
        $right_id = $left_id+1;  
    
        // get all children of this node
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
                    $msg = xarML('Valid values: IN or OUT');
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
                    $msg = xarML('Valid values: IN or OUT');
                    throw new BadParameterException(null, $msg);
               }
           break;
           default:
            $msg = xarML('Valid values: RIGHT or LEFT');
            throw new BadParameterException(null, $msg);
        }
        return $point_of_insertion;
    }

    // Get all the items of this Celko tree
    private function getItems($args)
    {
        extract($args);

        $indexby = 'default';

        $bindvars = array();
        $SQLquery = "SELECT
                            COUNT(P2.id) AS indent,
                            P1.id,
                            P1." . $this->initialization_celkoname . ",
                            P1." . $this->initialization_celkoparent_id . ",
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
               xarSession::setVar('errormsg', xarML('That item does not exist'));
               return array();
           }
           //$SQLquery .= " AND P1.left_id
           //               NOT BETWEEN ? AND ? ";
           $SQLquery .= " AND (P1." . $this->initialization_celkoleft_id . " < ? OR P1." . $this->initialization_celkoleft_id . "> ?)";
           $bindvars[] = $ecat['left_id']; $bindvars[] = $ecat['right_id'];
        }

        // Have to specify all selected attributes in GROUP BY
        $SQLquery .= " GROUP BY P1.id, P1." . $this->initialization_celkoname . ", P1." . $this->initialization_celkoparent_id . ", P1." . $this->initialization_celkoleft_id . ", P1." . $this->initialization_celkoright_id . " ";
        $SQLquery .= " ORDER BY P1." . $this->initialization_celkoleft_id;

    // cfr. xarcachemanager - this approach might change later
        $expire = xarModVars::get('categories','cache.userapi.getcat');
        if (!empty($expire)){
            $result = $this->dbconn->CacheExecute($expire,$SQLquery,$bindvars);
        } else {
            $result = $this->dbconn->Execute($SQLquery, $bindvars);
        }
        if (!$result) return;
        if ($result->EOF) return Array();

        $items = array();

        $index = -1;
        while (!$result->EOF) {
            list($indentation,
                    $id,
                    $name,
                    $parent,
                    $left,
                    $right
                   ) = $result->fields;
            $result->MoveNext();

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
    
    private function setCelkoValues($newid, $oldid)
    {
        if (isset($this->itemindices[$newid])) {
            $params = $this->itemindices[$newid];
            $oldid = $params[0];
        } else {
            // We'll still need to resolve this entry later
            // add this parent to the list of known parents for subsequent rounds
            $this->itemindices[$newid] = null;
            $this->itemsunresolved[$itemid] = $newid;
            $parent_id = 0;
        }
        
        // Set the left and right values null and let the updateposition method take care of them
        $this->right = null;
        $this->left = null;
        return true;
    }
    
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
        $exportvalue = serialize(array((int)$itemid, (int)$thisItem['parent_id'], (int)$thisItem['left_id'], (int)$thisItem['right_id']));
        return $exportvalue;
    }
}
?>