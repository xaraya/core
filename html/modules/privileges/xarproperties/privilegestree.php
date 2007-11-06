<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage privileges
 * @link http://xaraya.com/index.php/release/.html
 * @author Marc Lutolf <mfl@netspan.ch>
 */

sys::import('xaraya.structures.tree');
sys::import('modules.privileges.class.privileges');
/**
 * Handle Privileges Tree property
 */
class PrivilegesTreeProperty extends DataProperty
{
    public $id         = 30045;
    public $name       = 'privilegestree';
    public $desc       = 'PrivilegesTree';
    public $reqmodules = array('privileges');

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);

        if (!isset($allowtoggle)) $allowtoggle = 0;
        $this->tplmodule = 'privileges';
        $this->filepath   = 'modules/privileges/xarproperties';
        $this->privs = new xarPrivileges();
    }

    public function showInput(Array $data = array())
    {
        if (!isset($data['show'])) $data['show'] = 'assigned';
        $trees = array();
        foreach ($this->privs->gettoplevelprivileges($data['show']) as $entry) {
           $node = new TreeNode($entry['id']);
            $tree = new PrivilegesTree($node);
            $trees[] = $node->depthfirstenumeration();
//            var_dump($tree);echo "<br/><br/>";
//            var_dump($node);echo "<br/><br/>";
//            var_dump($node->depthfirstenumeration());echo "<br/><br/>";
        }
        $data['trees'] = $trees;
        return parent::showInput($data);
    }

}
// ---------------------------------------------------------------
class PrivilegesTree extends Tree
{
    function createnodes(TreeNode $node)
    {
        //FIXME this is too unwieldy and largely duplicating a similar query in xarPrivileges
        $dbconn = xarDB::getConn();
        $xartable = xarDB::getTables();
        $query = "SELECT p.id, p.name, r.name,
                         m.name, p.component, p.instance,
                         p.level,  p.description, pm.parentid
                  FROM " . $xartable['privileges'] . " p LEFT JOIN ". $xartable['realms'] . " r ON p.realmid = r.id
                  LEFT JOIN ". $xartable['modules'] . " m ON p.module_id = m.id
                  LEFT JOIN ". $xartable['privmembers'] . " pm ON p.id = pm.id
                  WHERE type = " . xarPrivileges::PRIVILEGES_PRIVILEGETYPE .
                  " ORDER BY p.name";
        $stmt = $dbconn->prepareStatement($query);
        // The fetchmode *needed* to be here, dunno why. Exception otherwise
        $result = $stmt->executeQuery($query,ResultSet::FETCHMODE_NUM);
        while($result->next()) {
            list($id, $name, $realm, $module, $component, $instance, $level,
                    $description, $parentid) = $result->fields;
            $nodedata = array('id' => $id,
                               'name' => $name,
                               'realm' => is_null($realm) ? 'All' : $realm,
                               'module' => $module,
                               'component' => $component,
                               'instance' => $instance,
                               'level' => $level,
                               'description' => $description,
                               'parent' => $parentid,);
            $this->treedata[] = $nodedata;
        }
        parent::createnodes($node);
    }
}

?>
