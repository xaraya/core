<?php
/**
 * Categories Module
 *
 * @package modules\categories
 * @subpackage categories
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/147.html
 *
 * @author Marc Lutolf <mfl@netspan.ch>
 */

class CategoryWorker extends xarObject
{
    protected $cattable;
    protected $basetable;
    protected $linktable;
    
    private $table;
    private $left   = "left_id";
    private $right  = "right_id";
    private $parent = "parent_id";

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
        $this->table     = $tables['categories'];
        $this->cattable  = $tables['categories'];
        $this->basetable = $tables['categories_basecategories'];
        $this->linktable = $tables['categories_linkage'];
    }
    
    public function setTable($x)  { $this->table = $x; }
    public function setLeft($x)   { $this->left = $x; }
    public function setRight($x)  { $this->right = $x; }
    public function setParent($x) { $this->parent = $x; }
    
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
        
        $q = new Query('SELECT', $this->cattable);
        $q->eq('id', $id);
        $q->addfield('name');
        $q->run();
        $result = $q->row();
        if (empty($result)) return xarML('Unknown category');
        return $result['name'];
    
        // CHECKME: this should be done elsewhere
        $name = rawurlencode($result['name']);
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
        if (empty($name)) throw new Exception(xarML('No name passed to name2id'));
        
        $q = new Query('SELECT', $this->cattable);
        $q->eq('name', $name);
        $q->addfield('id');
        $q->run();
        $result = $q->output();
        if (count($result) > 1) throw new Exception(xarML('Found a non-unique category name'));
        $result = current($result);
        if (empty($result)) return 0;
        return (int)$result['id'];
    }

    /**
     * Fetch category info from database by its id
     * 
     * @param int $id ID of the category to fetch information for
     * @return array|null Category data array, null if no category was found
     * @throws Exception Thrown if no ID was passed to the method
     */
    public function getInfo($id=0)
    {
        if (empty($id)) throw new Exception(xarML('No id passed to getInfo'));
        
        $q = new Query('SELECT', $this->table);
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
    
    // Legacy call
    public function getcatinfo($id=0)
    {
        return $this->getInfo($id);
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
        $q = new Query('SELECT', $this->table);
        if (is_array($id)) {
            if ($myself) {
                $c[] = $q->pin('id', $id);
                $c[] = $q->pin($this->parent, $id);
                $q->qor($c);
            } else {
                $q->in($this->parent, $id);
            }
        } else {
            if ($myself) {
                $c[] = $q->peq('id', $id);
                $c[] = $q->peq($this->parent, $id);
                $q->qor($c);
            } else {
                $q->eq($this->parent, $id);
            }
        }
        $q->setorder($this->left);
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
     * @param varchar $order
     * @return array|null Data array containing descendents of the given category, null if no children were found
     */
    public function getdescendents($id=0, $myself=0, $order='id')
    {
        $parent = $this->getInfo($id);

        $q = new Query('SELECT', $this->table);
        if ($myself) {
            $q->ge($this->left, $parent[$this->left]);
            $q->le($this->right, $parent[$this->right]);
        } else {
            $q->gt($this->left, $parent[$this->left]);
            $q->lt($this->right, $parent[$this->right]);
        }
        $q->setorder($this->left);
        if (!$q->run()) return;
        $result = $q->output();
        $descendents = array();
        
        if ($order == 'celko') $order = $this->left;
        else $order = 'id';
        
        foreach($result as $row) $descendents[$row[$order]] = $row;
        return $descendents;
    }

    /**
     * Delete a category and its children
     * 
     * @param int $id ID of the category to be deleted (along with its children
     * @param boolean $myself
     * @return array|null Data array containing descendents of the given category, null if no children were found
     */
    public function delete($id=0)
    {
        // Get this category's information
        $this_category = $this->getInfo($id);
        
        // Something went wrong
        if (empty($this_category)) return false;

        // Cannot delete the root category
        if (empty($this_category[$this->parent]) || ($this_category['id'] == 1) || ($this_category[$this->left] == 1)) return false;

        // Get the gap in indices that will be created by deleting
        $difference = $this_category[$this->right] - $this_category[$this->left] +1;

        // Delete this category enty and its descendents as entries of the table
        $q = new Query('DELETE', $this->table);
        $q->ge($this->left, (int)$this_category[$this->left]);
        $q->le($this->right, (int)$this_category[$this->right]);
        $q->run();

        // Get all the categories that contain the one(s) deleted
        // Their rights IDs are bigger, but their left IDs are smaller
        $q = new Query('SELECT', $this->table);
        $q->addfield('id');
        $q->addfield($this->right);
        $q->gt($this->right, (int)$this_category[$this->right]);
        $q->lt($this->left, (int)$this_category[$this->left]);
        $q->run();
        
        // Adjust their right indices
        $q1 = new Query('UPDATE', $this->table);
        
        foreach ($q->output() as $category) {
            $q1->addfield($this->right, (int)$category[$this->right] - $difference);
            $q1->eq('id', $category['id']);
            $q1->run();
            
            $q1->clearconditions();
            $q1->clearfields();
        }

        // Get all the categories to the right of the one(s) deleted
        $q = new Query('SELECT', $this->table);
        $q->addfield('id');
        $q->addfield($this->left);
        $q->addfield($this->right);
        $q->gt($this->left, (int)$this_category[$this->right]);
        $q->run();
        
        // Adjust their left and right indices
        $q1 = new Query('UPDATE', $this->table);
        
        foreach ($q->output() as $category) {
            $q1->addfield($this->left, (int)$category[$this->left] - $difference);
            $q1->addfield($this->right, (int)$category[$this->right] - $difference);
            $q1->eq('id', $category['id']);
            $q1->run();
            
            $q1->clearconditions();
            $q1->clearfields();
        }
                
        // All done
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
        $q = new Query('SELECT', $this->table);
        $q->eq($this->parent, 0);
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
        // Sanity check
        if ($this->table != $this->cattable) die("This method (getcatbases) can only be used in a categories context");
        
        extract($args);
        if (isset($object)) {
            // We are getting the base categories of an object
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
        } else {
            // We are getting the base categories of a module
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
    
            $bases = $q->output();
            if (!empty($bases)) {
                foreach (array_keys($bases) as $idx) {
                    if (isset($bases[$idx]['category_id']) && !isset($bases[$idx]['cid'])) {
                        $bases[$idx]['cid'] = $bases[$idx]['category_id'];
                    }
                }
            }
            return $bases;
        }
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
     * @param itemid the ID of the toplevel node of the subtree to copy
     * @return true if successful
     */
    public function appendTree($itemid, $args)
    {
        // Find the last top level category. We'll add the subtree after it
        sys::import('xaraya.structures.query');
        $q = new Query('SELECT', $this->table);
        $q->eq($this->parent, 0);       // These two conditions define the root
        $q->eq($this->left, 1);         // These two conditions define the root
        $q->setorder($this->right, 'DESC');
        if (!$q->run()) return;
        $result = $q->output();
        
        // Sanity check: abort immediately if the tree has more than one root
        if (count($result) > 1) {
            $msg = xarML('This tree has more than one root entry');
            die($msg);
        }
        // Or if it has no root
        if (count($result) < 1) {
            $msg = xarML('This tree has no root entry');
            die($msg);
        }
          
        // We have a single root (which is correct). Get it.
        $root = $q->row();

        // Define the left ID of the new top level category to append
        $new_left_id = (int)$root[$this->right];

        // Get the rows which we want to append, which are the category to clone and all its descendents
        $descendents = $this->getdescendents($itemid, 1);

        // Calculate the difference of the new top level category left ID to its old value
        $diff = $new_left_id - $descendents[$itemid][$this->left];

        // The parent of the new top level category is now a child of the root entry
        $descendents[$itemid][$this->parent] = $root['id'];
        
        // Set up an array with old and new itemids
        $oldnewids = array();

        // Update the IDs of each category and insert
        $newtoplevel = 0;
        $q = new Query('INSERT', $this->table);
        foreach ($descendents as $key => $child) {
            // Save the old ID and remove it as we will be creating an entry (ID needs to be empty)
            $oldid = $child['id'];
            unset($child['id']);

            // Adjust left, right and parent fields
            $child[$this->left] += $diff;
            $child[$this->right] += $diff;
            if ($child[$this->parent] != (int)$root['id']) $child[$this->parent] = $oldnewids[$child[$this->parent]];

            // If we passed any other args, overwrite the corresponding value if the $arg passed is a valid field
            foreach ($args as $key => $value) 
                if(isset($child[$key])) $child[$key] = $value;

            // Put the updated fields in to the proper format for adding to the query, and add them
            $fields = array();
            foreach ($child as $key => $value) $fields[] = array('name' => $key, 'value' => $value);
            $q->addfields($fields);

            // Insert the new entry and get its ID
            $q->run();
            $newid = $q->lastid($this->table, 'id');
            
            // Add this entry to the list of known old/new IDs that further descendents can use to update their parent fields
            $oldnewids[$oldid] = $newid;
            
            // Remove all the fields in preparation of the next round (we'll reuse this query)
            $q->clearfields();
            
            // Save the ID of the new top level category
            if ($child[$this->parent] == (int)$root['id']) $newtoplevel = $newid;
        }

        // Update the root entry's right ID to accomodate
        $q = new Query('UPDATE', $this->table);
        $q->eq('id', (int)$root['id']);
        $q->addfield($this->right, (int)$root[$this->right] + count($descendents) * 2);
        $q->run();
        
        // Return the top level of the appended tree
        return $newtoplevel;
    }
}
?>