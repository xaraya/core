<?php
/**
 * Categories Module
 *
 * @package modules\category
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/147.html
 *
 * @author Marc Lutolf <mfl@netspan.ch>
 */

    class CategoryWorker extends Object
    {
        protected $cattable;
        protected $basetable;
        protected $linktable;

        /**
         * Constructor for CategoryWorker
         * 
         * @param void N/A
         */
        public function __construct()
        {
            sys::import('xaraya.structures.query');
            sys::import('modules.categories.xartables');
            xarDB::importTables(categories_xartables());
            $tables =& xarDB::getTables();
            $this->cattable = $tables['categories'];
            $this->basetable = $tables['categories_basecategories'];
            $this->linktable = $tables['categories_linkage'];
        }
        
        /**
         * Fetches the name for a given id from the database
         * 
         * @param int $id ID of Category
         * @return string|null Returns name as string or null if category was not found
         * @throws Exception Thrown if no ID was given
         */
        public function id2name($id=0)
        {
            if (empty($id)) throw new Exception(xarML('No id passed to id2name'));
            
            $query = "SELECT name FROM $this->catstable WHERE id = ?";
            $result = $dbconn->Execute($query,array($cid));
            if (!$result) return;
        
            list($name) = $result->fields;
            $result->Close();
        
            $name = rawurlencode($name);
            $name = preg_replace('/%2F/','/',$name);
            return $name;
        }
        
        /**
         * Fetches ID of category given by its name from the database
         * 
         * @param string $name Name of the category to fetch
         * @return int|null Returns the ID of a category or null if no category was found
         * @throws Exception Thrown if $name was empty
         */
        public function name2id($name="Top")
        {
            if (empty($id)) throw new Exception(xarML('No id passed to name2id'));
            
            $query = "SELECT id FROM $this->catstable WHERE name = ?";
            $result = $dbconn->Execute($query,array($cid));
            if (!$result) return;
        
            list($id) = $result->fields;
            $result->Close();
            return $id;
        }

        /**
         * Fetch category info from database by its id
         * 
         * @param int $id ID of the category to fetch information for
         * @return array|null Category data array, null if no category was found
         * @throws Exception Thrown if no ID was passed to the method
         */
        public function getcatinfo($id=0)
        {
            if (empty($id)) throw new Exception(xarML('No id passed to getcatinfo'));
            
            $q = new Query('SELECT', $this->cattable);
            if (is_array($id)) {
                $q->in('id', $id);
                if (!$q->run()) return;
                $result = $q->output();
                $info = array();
                foreach($result as $row) $info[$row['id']] = $row;
            } else {
                $q->eq('id', $id);
                if (!$q->run()) return;
                $info = $q->row();
            }
            return $info;
        }

        /**
         * Fetch the children of a category
         * 
         * @param int $id ID of the parent category
         * @param boolean $myself
         * @return array|null Data array containing children of the given category, null if no children were found
         */
        public function getchildren($id=0,$myself=0)
        {
            $q = new Query('SELECT', $this->cattable);
            if (is_array($id)) {
                if ($myself) {
                    $c[] = $q->pin('id', $id);
                    $c[] = $q->pin('parent_id', $id);
                    $q->qor($c);
                } else {
                    $q->in('parent_id', $id);
                }
            } else {
                if ($myself) {
                    $c[] = $q->peq('id', $id);
                    $c[] = $q->peq('parent_id', $id);
                    $q->qor($c);
                } else {
                    $q->eq('parent_id', $id);
                }
            }
            $q->setorder('left_id');
            if (!$q->run()) return;
            $result = $q->output();
            $children = array();
            foreach($result as $row) $children[$row['id']] = $row;
            return $children;
        }

        /**
         * Fetch the descendents of a category
         * 
         * @param int $id ID of the parent category
         * @param boolean $myself
         * @return array|null Data array containing descendents of the given category, null if no children were found
         */
        public function getdescendents($id=0,$myself=0)
        {
            $parent = $this->getcatinfo($id);
            
            $q = new Query('SELECT', $this->cattable);
            if ($myself) {
                $q->ge('left_id', $parent['left_id']);
                $q->le('right_id', $parent['right_id']);
            } else {
                $q->gt('left_id', $parent['left_id']);
                $q->lt('right_id', $parent['right_id']);
            }
            $q->setorder('left_id');
            if (!$q->run()) return;
            $result = $q->output();
            $descendents = array();
            foreach($result as $row) $descendents[$row['id']] = $row;
            return $descendents;
        }

        /**
         * Delete a category and its children
         * 
         * @param int $id ID of the parent category
         * @param boolean $myself
         * @return array|null Data array containing descendents of the given category, null if no children were found
         */
        public function delete($id=0)
        {
            $parent = $this->getcatinfo($id);
            
            $q = new Query('DELETE', $this->cattable);
                $q->ge('left_id', $parent['left_id']);
                $q->le('right_id', $parent['right_id']);
            if (!$q->run()) return;
            return true;
        }

        /**
         * Fetch top level categories from the tree in the database
         * 
         * @param void N/A
         * @return array Category data array
         */
        public function gettoplevel()
        {
            $q = new Query('SELECT', $this->cattable);
            $q->eq('parent_id', 0);
            if (!$q->run()) return;
            $result = $q->output();
            return $result;
        }

        /**
         * Fetch the top level count
         * 
         * @param void N/A
         * @return int Count of top level categories
         */
        public function gettoplevelcount()
        {
            return count($this->gettoplevel());
        }

        /**
         * Fetch category bases from database
         * 
         * @param array $args Parameter data array
         * @return array Category bases data array
         */
        public function getcatbases($args)
        {
            extract($args);
            if (!isset($object)) throw new Exception(xarML('Nissing object for getcatbases'));
            sys::import('modules.dynamicdata.class.objects.master');
            $object = DataObjectMaster::getObject(array('name' => $object));

            if (!isset($property) && isset($object->properties['categories'])) {
                $property = $object->properties['categories'];
            } elseif (isset($property)) {
                $property = $object->properties[$property];
            } else {
                return array();
            }
            
            $configuration = $property->initialization_basecategories;
            $base_values = $configuration[1];
            $bases = array();
            foreach ($base_values as $base_value) {
                $base = (int)$base_value[1][0];
                $bases[] = $base;
            }
            return $bases;
            
            $xartable =& xarDB::getTables();
        
            sys::import('xaraya.structures.query');
            $q = new Query('SELECT');
            $q->addtable($xartable['categories_basecategories'],'base');
            $q->addtable($xartable['categories'],'category');
            $q->leftjoin('base.category_id','category.id');
            $q->addfield('base.id AS id');
            $q->addfield('base.category_id AS category_id');
            $q->addfield('base.name AS name');
            $q->addfield('base.module_id AS module_id');
            $q->addfield('base.itemtype AS itemtype');
            $q->addfield('category.left_id AS left_id');
            $q->addfield('category.right_id AS right_id');
            // Aliases for 1.x modules calling categories
        // FIXME: no way to have get the same field twice with different aliases ?
            //$q->addfield('base.category_id AS cid');
            if (!empty($module))  $q->eq('module_id',(int)xarMod::getRegID($module));
            if (!empty($module_id))  $q->eq('module_id',(int)$module_id);
            if (isset($itemtype))  $q->eq('itemtype',(int)$itemtype);
            $q->addorder('base.id');
        //    $q->qecho();
            if (!$q->run()) return;
        
            $output = $q->output();
            if (!empty($output)) {
                foreach (array_keys($output) as $idx) {
                    if (isset($output[$idx]['category_id']) && !isset($output[$idx]['cid'])) {
                        $output[$idx]['cid'] = $output[$idx]['category_id'];
                    }
                }
            }
            return $output;
        }

        /**
         * Fetch count of category bases from database
         * 
         * @param array $args Parameter data array
         * @return int Count of Category bases
         */
        public function getcatbasecount($args)
        {
            return count($this->getcatbases($args));
        }

        /**
         * Append a subtree to the tree
         * 
         * @param itemid the ID of the toplevel node of the subtree to move
         * @return true if successful
         */
        public function appendTree($itemid)
        {
            // Find the last top level category. We'll add the subtree after it
            $q = new Query('SELECT', $this->cattable);
            $q->addfield('right_id', 0);
            $q->eq('parent_id', 0);
            $q->setorder('right_id', 'DESC');
            if (!$q->run()) return;
            $result = $q->row();
            
            // Define the left ID of the new top level category to append
            $left_id = (int)$result['right_id'] + 1;
            
            // Get the rows which we want to append, which are the category to clone and all its children
            $children = $this->getdescendents($itemid, 1);
            
            // Calculate the difference of the new top level category left ID to its old value
            $diff =  $left_id - $children[$itemid]['left_id'];
            
            // The parent of the new top level category is now zero
            $children[$itemid]['parent_id'] = 0;
            
            // Set up an array with old and new itemids
            $oldnewids = array();

            // Update the IDs of each category and insert
            $q = new Query('INSERT', $this->cattable);//echo "<pre>";var_dump($children);exit;
            foreach ($children as $key => $child) {
                $oldid = $child['id'];
                unset($child['id']);
                $child['left_id'] += $diff;
                $child['right_id'] += $diff;
                if ($child['parent_id'] != 0) $child['parent_id'] = $oldnewids[$child['parent_id']];
                $fields = array();
                foreach ($child as $key => $value) $fields[] = array('name' => $key, 'value' => $value);
                $q->addfields($fields);
                $q->run();
                $newid = $q->lastid($this->cattable, 'id');
                $oldnewids[$oldid] = $newid;
                $q->clearfields();
            }
            return $diff;
        }

    }
?>
