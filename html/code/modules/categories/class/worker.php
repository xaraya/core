<?php
/**
 * Categories Module
 *
 * @package modules
 * @subpackage categories module
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

        public function __construct()
        {
            sys::import('xaraya.structures.query');
            sys::import('modules.categories.xartables');
            xarDB::importTables(categories_xartables());
            $tables = xarDB::getTables();
            $this->cattable = $tables['categories'];
            $this->basetable = $tables['categories_basecategories'];
            $this->linktable = $tables['categories_linkage'];
        }
        
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

        public function getchildren($id=0,$myself=0)
        {
//            if (empty($id)) throw new Exception(xarML('No id passed to getchildren'));
            
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
            $q->addorder('left_id');
            if (!$q->run()) return;
            $result = $q->output();
            $children = array();
            foreach($result as $row) $children[$row['id']] = $row;
            return $children;
        }

        public function gettoplevel()
        {
            $q = new Query('SELECT', $this->cattable);
            $q->eq('parent_id', 0);
            if (!$q->run()) return;
            $result = $q->output();
            return $result;
        }

        public function gettoplevelcount()
        {
            return count($this->gettoplevel());
        }

        public function getcatbases($args)
        {
            extract($args);
            $xartable = xarDB::getTables();
        
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
            if (!empty($module))  $q->eq('module_id',xarMod::getRegID($module));
            if (!empty($module_id))  $q->eq('module_id',$module_id);
            if (isset($itemtype))  $q->eq('itemtype',$itemtype);
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

        public function getcatbasecount($args)
        {
            return count($this->getcatbases($args));
        }

    }
?>
