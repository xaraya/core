<?php
/**
 * CelkoPosition Property
 *
 * @package properties
 * @subpackage celkoposition property
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
 */
class CelkoPositionProperty extends DataProperty
{
    public $id           = 30074;
    public $name         = 'celkoposition';
    public $desc         = 'Celko Position';
    public $reqmodules   = array('base');

    public $reference_id = 0;               // The ID of the parent item
    public $include_reference = 1;          // Get a reference to the parent object
    public $moving;
    public $position;
    public $rightorleft;
    public $inorout;
//    public $parent;
    public $catexists;
    public $dbconn;
    public $parentindices     = array();    // helper variable to hold parent items when importing
    public $parentunresolveds = array();    // helper variable to hold unresolved parent references (key) and their itemids (value)
    
    public $initialization_celkotable     = 'xar_categories';
    public $initialization_celkoname      = 'name';
    public $initialization_celkoparent_id = 'parent_id';
    public $initialization_celkoright_id  = 'right_id';
    public $initialization_celkoleft_id   = 'left_id';

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'base';
        $this->filepath  = 'modules/base/xarproperties';
        $this->dbconn = xarDB::getConn();
    }

    public function checkInput($name = '', $value = null)
    {
        if (!xarVarFetch($name . '_reference_id', 'int:0', $reference_id)) return;
        if (!xarVarFetch($name . '_position', 'enum:1:2:3:4', $position)) return;
        switch (intval($position)) {
            case 1: // above - same level
            default:
                $this->rightorleft = 'left';
                $this->inorout = 'out';
                break;
            case 2: // below - same level
                $this->rightorleft = 'right';
                $this->inorout = 'out';
                break;
            case 3: // below - child item
                $this->rightorleft = 'right';
                $this->inorout = 'in';
                break;
            case 4: // above - child item
                $this->rightorleft = 'left';
                $this->inorout = 'in';
                break;
        }
        $this->reference_id = $reference_id;
        return true;
    }

    public function createValue($itemid=0)
    {
        $n = $this->countItems($itemid);
        if ($n > 1) {
            // There is more than one item for this itemid. That's a problem.
            throw new Exception(xarML('More than one item for the itemid value #(1)',$itemid));
        } elseif ($n == 1) {
            // There is one item for this itemid. This means it was already created
            // Usually this means the same datasource used for this property and the others of this object item
            if ($this->value) $parentid = $this->unpackValue($itemid);
            else $parentid = 0;
            
            // CHECKME: why do we need to run updateposition AND updateValue?
            $itemid = $this->updateposition($itemid, $parentid);
//            $this->reference_id = $parentid;
//            $this->rightorleft = 'RIGHT';
//            $this->inorout = 'IN';
            $this->updateValue($itemid);

            // We updated a position. now go back and see if any of the unresolveds we have can be resolved
            foreach ($this->parentunresolveds as $key => $value) {
                if (isset($this->parentindices[$value])) {
                    $this->reference_id = $this->parentindices[$value];
                    $this->rightorleft = 'RIGHT';
                    $this->inorout = 'IN';
                    $this->updateValue($key);
                    unset($this->parentunresolveds[$key]);
                }
            }
            
        } else {
            // There is no item for this itemid yet
            // The datasource for this property is likely different from that of the other properties of this object.
            // We'll need to create an item.
            if ($this->value) {
            // FIXME: this has not been tested!!!
                $this->unpackValue();
            } else {
                // No value, this insert is via the UI
                // Obtain current information on the reference item
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
        return parent::showInput($data);

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
    
    private function countItems($itemid)
    {
        $sql = "SELECT COUNT(id) AS childnum
                  FROM " . $this->initialization_celkotable . " WHERE id = " . $itemid;
        $result = $this->dbconn->Execute($sql);
        if (!$result) return;
        $num = $result->fields[0];
        $result->Close();
        return $num;
    }

    function build_tree($parent_id, $left_id)
    {       
       // the right value of this node is the left value + 1  
       $right_id = $left_id+1;  
    
       // get all children of this node  
        $q = "SELECT id
              FROM " . $this->initialization_celkotable;
        $q .= " WHERE " . $this->initialization_celkoparent_id . " = ?";
        $bindvars = array($parent_id);
        $result = $this->dbconn->Execute($q, $bindvars);
    
        while (!$result->EOF) {
            list($child_id) = $result->fields;
           // recursive execution of this function for each  
           // child of this node  
           // $right_id is the current right value, which is  
           // incremented by the rebuild_tree function  
           $right_id = $this->build_tree($child_id, $right_id);  
           $result->MoveNext();
       }  
       // we've got the left value, and now that we've processed  
       // the children of this node we also know the right value  
        $bindvars = array($left_id);
        $bindvars[] = $right_id;
        $bindvars[] = $parent_id;
        $q = "UPDATE " . $this->initialization_celkotable;
        $q .= " SET " . $this->initialization_celkoleft_id . " = ?, ";
        $q .= $this->initialization_celkoright_id . " = ? ";
        $q .= "WHERE id = ?";  
        $result = $this->dbconn->Execute($q, $bindvars);
     
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

/*          CHECKME: how do we handle privileges?
            if (!xarSecurityCheck('ViewCategories',0,'Category',"$name:$cid")) {
                 continue;
            }
*/
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

    // Takes the serialized value in $this->value and assigns its unserialized values to their proper places
    private function unpackValue($itemid)
    {
        // Unpack the values of this property
        $params = unserialize($this->value);
        
        // Get the value for the reference ID (parent)
        $this->reference_id = $params[$this->initialization_celkoparent_id];
        $this->setCelkoValues($this->reference_id, $params['id']);
                
        // add this itemid to the list of known parents for subsequent rounds
        $this->parentindices[$params['id']] = $itemid;
        return $this->reference_id;
    }
    
    private function setCelkoValues($parentid, $itemid)
    {
        if (isset($this->parentindices[$parentid])) {
            $this->reference_id = $this->parentindices[$parentid];
        } else {
            // We'll still need to resolve this entry later
            // add this parent to the list of known parents for subsequent rounds
            $this->parentindices[$parentid] = null;
            $this->parentunresolveds[$itemid] = $parentid;
            $this->reference_id = 0;
        }
        
        // Set the left and right values null and let the updateposition method take care of them
        $this->right = null;
        $this->left = null;
        return true;
    }
}
?>