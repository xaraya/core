<?php
class CelkoPositionProperty extends DataProperty
{
    public $id           = 30074;
    public $name         = 'celkoposition';
    public $desc         = 'Celko Position';
    public $reqmodules   = array('categories');

    public $refcid;
    public $moving;
    public $position;
    public $rightorleft;
    public $inorout;
    public $parent;
    public $catexists;
    
    public $initialization_itemstable;
    public $initialization_celkoname = 'name';
    public $initialization_celkoparent_id = 'parent_id';
    public $initialization_celkoright_id = 'right_id';
    public $initialization_celkoleft_id  = 'left_id';
    
    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->template = 'celkoposition';
        $this->tplmodule = 'categories';
        $this->filepath   = 'modules/categories/xarproperties';
        
        sys::import('modules.categories.xartables');
        xarDB::importTables(categories_xartables());
        $xartable = xarDB::getTables();
        $this->initialization_itemstable = $xartable['categories'];
    }

    public function checkInput($name = '', $value = null)
    {
        if (!xarVarFetch($name . '_refcid', 'int:0', $refcid)) return;
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
                case 3: // below - child category
                    $this->rightorleft = 'right';
                    $this->inorout = 'in';
                    break;
                case 4: // above - child category
                    $this->rightorleft = 'left';
                    $this->inorout = 'in';
                    break;
            }
        $this->refcid = $refcid;
        return true;
    }

    public function createValue($itemid=0)
    {
        $n = $this->countitems();
        if ($n == 1) {
            $itemid = $this->updateposition($itemid);
        } else {

           // Obtain current information on the reference category
           $cat = $this->getiteminfo($this->refcid);

           if ($cat == false) {
               xarSession::setVar('errormsg', xarML('That category does not exist'));
               return false;
           }

           $this->right = $cat['right_id'];
           $this->left = $cat['left_id'];

           /* Find out where you should put the new category in */
           if (
               !($point_of_insertion =
                    $this->find_point_of_insertion(
                       array('inorout' => $this->inorout,
                               'rightorleft' => $this->rightorleft,
                               'right' => $this->right,
                               'left' => $this->left
                       )
                   )
              )
              )
           {
               return false;
           }

            /* Find the right parent for this category */
            if (strtolower($this->inorout) == 'in') {
                $parent = (int)$this->refcid;
            } else {
                $parent = (int)$cat['parent_id'];
            }
            $itemid = $this->updateposition($itemid,$parent,$point_of_insertion);
        }
        return true;
    }

    public function updateValue($itemid=0)
    {
        // Obtain current information on the category
        $cat = $this->getiteminfo($itemid);

        if ($cat == false) {
           xarSession::setVar('errormsg', xarML('That category does not exist'));
           return false;
        }

        // Get datbase setup
        $dbconn = xarDB::getConn();

       // Obtain current information on the reference category
       $refcat = $this->getiteminfo($this->refcid);

       if ($refcat == false) {
           xarSession::setVar('errormsg', xarML('That category does not exist'));
           return false;
       }

       // Checking if the reference ID is of a child or itself
       if (
           ($refcat['left_id'] >= $cat['left_id'])  &&
           ($refcat['left_id'] <= $cat['right_id'])
          )
       {
            $msg = xarML('Category references siblings.');
            throw new BadParameterException(null, $msg);
       }

       // Find the needed variables for moving things...
       $point_of_insertion =
                   $this->find_point_of_insertion(
                       array('inorout' => $this->inorout,
                               'rightorleft' => $this->rightorleft,
                               'right' => $refcat['right_id'],
                               'left' => $refcat['left_id']
                       )
                   );
       $size = $cat['right_id'] - $cat['left_id'] + 1;
       $distance = $point_of_insertion - $cat['left_id'];

       // If necessary to move then evaluate
       if ($distance != 0) { // It´s Moving, baby!  Do the Evolution!
          if ($distance > 0)
          { // moving forward
              $distance = $point_of_insertion - $cat['right_id'] - 1;
              $deslocation_outside = -$size;
              $between_string = ($cat['right_id'] + 1)." AND ".($point_of_insertion - 1);
          }
          else
          { // $distance < 0 (moving backward)
              $deslocation_outside = $size;
              $between_string = $point_of_insertion." AND ".($cat['left_id'] - 1);
          }

          // TODO: besided portability, also check performance here
          $SQLquery = "UPDATE " . $this->initialization_itemstable . " SET
                       left_id = CASE
                        WHEN " . $this->initialization_celkoright_id . " BETWEEN ".$cat['left_id']." AND ".$cat['right_id']."
                           THEN " . $this->initialization_celkoleft_id . " + ($distance)
                        WHEN " . $this->initialization_celkoleft_id . " BETWEEN $between_string
                           THEN " . $this->initialization_celkoleft_id . " + ($deslocation_outside)
                        ELSE " . $this->initialization_celkoleft_id . "
                        END,
                      " . $this->initialization_celkoright_id . " = CASE
                        WHEN " . $this->initialization_celkoright_id . " BETWEEN ".$cat['left_id']." AND ".$cat['right_id']."
                           THEN " . $this->initialization_celkoright_id . " + ($distance)
                        WHEN " . $this->initialization_celkoright_id . " BETWEEN $between_string
                           THEN " . $this->initialization_celkoright_id . " + ($deslocation_outside)
                        ELSE " . $this->initialization_celkoright_id . "
                        END
                     ";
                     // This seems SQL-92 standard... Its a good test to see if
                     // the databases we are supporting are complying with it. This can be
                     // broken down in 3 simple UPDATES which shouldnt be a problem with any database

            $result = $dbconn->Execute($SQLquery);
            if (!$result) return;

          /* Find the right parent for this category */
          if (strtolower($this->inorout) == 'in') {
              $parent_id = $this->refcid;
          } else {
              $parent_id = $refcat['parent_id'];
          }
          // Update parent id
          $SQLquery = "UPDATE " . $this->initialization_itemstable .
                       " SET " . $this->initialization_celkoparent_id . " = ?
                       WHERE id = ?";
        $result = $dbconn->Execute($SQLquery,array($parent_id, $itemid));
        if (!$result) return;

       } 
    }

    public function showInput(Array $data = array())
    {
        $data['itemid'] = isset($data['itemid']) ? $data['itemid'] : $this->value;
        if (!empty($data['itemid'])) {        
            $data['category'] = $this->getiteminfo($data['itemid']);
            $categories = $this->getcat(array('cid' => false,
                                              'eid' => $data['itemid']));
            $data['cid'] = $data['itemid'];
        } else {
            $data['category'] = Array('left_id'=>0,'right_id'=>0,'name'=>'','description'=>'', 'image' => '');
            $categories = $this->getcat(array('cid' => false));
            $data['cid'] = null;
        }
        
        $category_Stack = array ();

        foreach ($categories as $key => $category) {
            $categories[$key]['slash_separated'] = '';

            while ((count($category_Stack) > 0 ) &&
                   ($category_Stack[count($category_Stack)-1]['indentation'] >= $category['indentation'])
                  ) {
               array_pop($category_Stack);
            }

            foreach ($category_Stack as $stack_cat) {
                    $categories[$key]['slash_separated'] .= $stack_cat['name'].'&#160;/&#160;';
            }

            array_push($category_Stack, $category);
            $categories[$key]['slash_separated'] .= $category['name'];
        }

        $data['categories'] = $categories;
        return parent::showInput($data);

    }
    
    function updateposition($itemid=0, $parent=0, $point_of_insertion=1) 
    {
        
        $dbconn = xarDB::getConn();
        $bindvars = array();
        $bindvars[1] = array();
        $bindvars[2] = array();
        $bindvars[3] = array();

        /* Opening space for the new node */
        $SQLquery[1] = "UPDATE " . $this->initialization_itemstable .
                        " SET " . $this->initialization_celkoright_id . " = " . $this->initialization_celkoright_id . " + 2
                        WHERE " . $this->initialization_celkoright_id . ">= ?";
        $bindvars[1][] = $point_of_insertion;

        $SQLquery[2] = "UPDATE " . $this->initialization_itemstable .
                        " SET " . $this->initialization_celkoleft_id . " = " . $this->initialization_celkoleft_id . " + 2
                        WHERE " . $this->initialization_celkoleft_id . ">= ?";
        $bindvars[2][] = $point_of_insertion;
        // Both can be transformed into just one SQL-statement, but i dont know if every database is SQL-92 compliant(?)

        $SQLquery[3] = "UPDATE " . $this->initialization_itemstable . " SET " .
                                    $this->initialization_celkoparent_id . " = ?," .
                                    $this->initialization_celkoleft_id . " = ?," .
                                    $this->initialization_celkoright_id . " = ?
                                     WHERE id = ?";
        $bindvars[3] = array($parent, $point_of_insertion, $point_of_insertion + 1,$itemid);

        for ($i=1;$i<4;$i++) if (!$dbconn->Execute($SQLquery[$i],$bindvars[$i])) return;
    }

    function find_point_of_insertion($args)
    {
        extract($args);

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
    
    function getiteminfo($id) 
    {
        sys::import('xaraya.structures.query');
        $q = new Query('SELECT', $this->initialization_itemstable);
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
    
    function countitems()
    {
        // Database information
        $dbconn = xarDB::getConn();
        $sql = "SELECT COUNT(id) AS childnum
                  FROM " . $this->initialization_itemstable;

        $result = $dbconn->Execute($sql);
        if (!$result) return;
        $num = $result->fields[0];
        $result->Close();
        return $num;
    }

    function getcat($args)
    {
        extract($args);

        $dbconn = xarDB::getConn();
        $indexby = 'default';

        $bindvars = array();
        $SQLquery = "SELECT
                            COUNT(P2.id) AS indent,
                            P1.id,
                            P1." . $this->initialization_celkoname . ",
                            P1." . $this->initialization_celkoparent_id . ",
                            P1." . $this->initialization_celkoleft_id . ",
                            P1." . $this->initialization_celkoright_id . 
                       " FROM " . $this->initialization_itemstable . " P1, " .
                            $this->initialization_itemstable . " P2
                      WHERE P1." . $this->initialization_celkoleft_id . " 
                         >= P2." . $this->initialization_celkoleft_id . " 
                        AND P1." . $this->initialization_celkoleft_id . " 
                         <= P2." . $this->initialization_celkoright_id;

        if (isset($eid) && !is_array($eid) && $eid != false) {
           $ecat = $this->getiteminfo($eid);
           if ($ecat == false) {
               xarSession::setVar('errormsg', xarML('That category does not exist'));
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
            $result = $dbconn->CacheExecute($expire,$SQLquery,$bindvars);
        } else {
            $result = $dbconn->Execute($SQLquery, $bindvars);
        }
        if (!$result) return;
        if ($result->EOF) return Array();

        $categories = array();

        $index = -1;
        while (!$result->EOF) {
            list($indentation,
                    $cid,
                    $name,
                    $parent,
                    $left,
                    $right
                   ) = $result->fields;
            $result->MoveNext();

            if (!xarSecurityCheck('ViewCategories',0,'Category',"$name:$cid")) {
                 continue;
            }

            if ($indexby == 'cid') {
                $index = $cid;
            } else {
                $index++;
            }

            // are we looking to have the output in the "standard" form?
            if (!empty($dropdown)) {
                $categories[$index+1] = Array(
                    'id'         => $cid,
                    'name'        => $name,
                );
            } else {
                $categories[$index] = Array(
                    'indentation' => $indentation,
                    'cid'         => $cid,
                    'name'        => $name,
                    'parent'      => $parent,
                    'left'        => $left,
                    'right'       => $right,
                );
            }
        }
        $result->Close();

        if (!empty($dropdown)) {
            $categories[0] = array('id' => 0, 'name' => '');
        }

        return $categories;
    }

}
?>