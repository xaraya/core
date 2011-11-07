<?php
/**
 * Categories Module
 *
 * @package modules
 * @subpackage categories module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
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
            sys::import('modules.categories.xartables');
            $tables = xarDB::importTables(categories_xartables());
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
        
            list($parent,$name) = $result->fields;
            $result->Close();
        
            $name = rawurlencode($name);
            $name = preg_replace('/%2F/','/',$name);
            return $name;
        }
        
        public function getcatinfo($id=0)
        {
            if (empty($id)) throw new Exception(xarML('No id passed to getcatinfo'));
            
            $q = new Query('SELECT', $this->cattable);
            if (is_array()) {
                $q->in('id', $id);
                if (!$q->run()) return;
                $info = $q->row();
            } else {
                $q->eq('id', $id);
                if (!$q->run()) return;
                $result = $q->output();
                $info = array();
                foreach($result as $row) $info[$row['id']] = $row;
            }
            return $info;
        }

        public function getchildren($id=0,$myself=0)
        {
            if (empty($id)) throw new Exception(xarML('No id passed to getchildren'));
            
            $q = new Query('SELECT', $this->cattable);
            if (is_array()) {
                if ($myself) {
                    $c[] = $q->pin('id', $id);
                    $c[] = $q->pin('parent_id', $id);
                    $q->qor($c);
                } else {
                    $q->in('id', $id);
                }
            } else {
                if ($myself) {
                    $c[] = $q->peq('id', $id);
                    $c[] = $q->peq('parent_id', $id);
                    $q->qor($c);
                } else {
                    $q->eq('id', $id);
                }
            }
            $q->addorder('ledt_id');
            $result = $q->output();
            $children = array();
            foreach($result as $row) $children[$row['id']] = $row;
            return $children;
        }
    }
?>
