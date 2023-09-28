<?php
/**
 * @package modules\privileges
 * @subpackage privileges
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1098.html
 *
 * @author Marc Lutolf <mfl@netspan.ch>
 */

sys::import('xaraya.structures.tree');
sys::import('modules.privileges.class.privileges');
sys::import('modules.dynamicdata.class.properties.base');

/**
 * Handle Privileges Tree property
 */
class PrivilegesTreeProperty extends DataProperty
{
    public $id         = 30045;
    public $name       = 'privilegestree';
    public $desc       = 'PrivilegesTree';
    public $reqmodules = array('privileges');
	public $privs;
	/**
	 * Create an instance of this dataproperty<br/>
	 * - It belongs to the privileges module<br/>
	 * - It has its own input/output templates<br/>
	 * - it is found at modules/privileges/xarproperties<br/>
	 *
	 */
    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);

        if (!isset($allowtoggle)) $allowtoggle = 0;
        $this->tplmodule = 'privileges';
        $this->filepath   = 'modules/privileges/xarproperties';
        $this->privs = new xarPrivileges();
    }
	
	/**
	 * Display a options for input to show wheather you want to display input for an instance or not.
	 * 
	 * @param array<string, mixed> $data An array of input parameters
	 * @return string     HTML markup to display the property for input on a web page
	 */	
    public function showInput(Array $data = array())
    {
        if (!isset($data['show'])) $data['show'] = 'assigned';
        $trees = array();
        foreach ($this->privs->gettoplevelprivileges($data['show']) as $entry) {
            $node = new TreeNode($entry['id']);
            $tree = new PrivilegesTree($node);
            $trees[] = $node->depthfirstenumeration();
        }
        $data['trees'] = $trees;
        return parent::showInput($data);
    }

}
// ---------------------------------------------------------------
class PrivilegesTree extends Tree
{
	
	/**
	*  Give privileges to user to create nodes
	* 
	* @param  TreeNode data An array of input parameters
	* @return void 
	*/
    function createnodes(TreeNode $node)
    {
        //FIXME this is too unwieldy and largely duplicating a similar query in xarPrivileges
        $dbconn = xarDB::getConn();
        $xartable =& xarDB::getTables();
        $q = new Query('SELECT');
        // Add fields
        $q->addfields("p.id AS id, p.name AS name, p.component AS component, p.instance AS instance, p.level AS level, p. description AS description");
        $q->addfields("r.name AS realm, m.name AS module, pm.parent_id AS parent");
        // Add tables
        $q->addtable($xartable['privileges'], 'p');
        $q->addtable($xartable['modules'], 'm');
        $q->leftjoin('p.module_id', 'm.id');
        $q->addtable($xartable['realms'], 'r');
        $q->leftjoin('p.realm_id', 'r.id');
        $q->addtable($xartable['privmembers'], 'pm');
        $q->leftjoin('p.id', 'pm.privilege_id');
        // Add conditions
        $q->eq('p.itemtype', xarPrivileges::PRIVILEGES_PRIVILEGETYPE);
        // Add ordering
        $q->setorder('p.name');
        $q->run();
        
        foreach ($q->output as $row) {
        	if ($row['realm'] == null) $row['realm'] = 'All';
            $this->treedata[] = $row;
        }
        parent::createnodes($node);
    }
}
