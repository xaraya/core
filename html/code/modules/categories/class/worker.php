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
                if (!$q->run()) return;
                $q->eq('id', $id);
                $result = $q->output();
                $info = array();
                foreach($result as $row) $info[$row['id']] = $row;
            }
            return $info;
        }
    }
?>
