<?php
/**
 *
 * PrivilegesTree Property
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2006 by to be added
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link to be added
 * @subpackage Privileges Module
 * @author Marc Lutolf <mfl@netspan.ch>
 *
 */

sys::import('modules.base.xarproperties.Dynamic_Tree_Property');
sys::import('modules.privileges.xarprivileges');

class Dynamic_PrivilegesTree_Property extends Dynamic_Tree_Property
{
    public $privs;

    // convenience variables to hold strings referring to pictures
    public $el = '<img src="modules/privileges/xarimages/el.gif" alt="" style="vertical-align: middle" />';
    public $tee = '<img src="modules/privileges/xarimages/T.gif" alt="" style="vertical-align: middle" />';
    public $aye = '<img src="modules/privileges/xarimages/I.gif" alt="" style="vertical-align: middle" />';
    public $bar = '<img src="modules/privileges/xarimages/s.gif" alt="" style="vertical-align: middle" />';
    public $emptybox = '<img class="xar-privtree-box" src="modules/privileges/xarimages/k1.gif" alt="" style="padding-left: 0.1em; vertical-align: middle" />';
    public $expandedbox = '<img class="xar-privtree-box" src="modules/privileges/xarimages/k2.gif" alt="" style="padding-left: 0.1em; vertical-align: middle" onclick="toggleBranch(this, this.parentNode.lastChild);" />';
    public $blank = '<img src="modules/privileges/xarimages/blank.gif" alt="" style="vertical-align: middle" />';
    public $collapsedbox = '<img class="xar-privtree-box" src="modules/privileges/xarimages/k3.gif" alt="" style="padding-left: 0.1em; vertical-align: middle" onclick="toggleBranch(this, this.parentNode.lastChild);" />';
    public $bigblank ='<span style="padding-left: 0.25em; padding-right: 0.25em;"><img src="modules/privileges/xarimages/blank.gif" alt="" style="vertical-align: middle; width: 16px; height: 16px;" /></span>';
    public $biggerblank ='<span style="padding-left: 0.25em; padding-right: 0.5em;"><img src="modules/privileges/xarimages/blank.gif" alt="" style="vertical-align: middle; width: 16px; height: 16px;" /></span>';

    // we'll use this to check whether a group has already been processed
    public $alreadydone;

    function __construct($args)
    {
        parent::__construct($args);

        extract($args);
        if (!isset($allowtoggle)) $allowtoggle = 0;
        $this->tplmodule = 'privileges';
        $this->filepath   = 'modules/privileges/xarproperties';
        $this->options = array();
        $this->privs = new xarPrivileges();
    }

    static function getRegistrationInfo()
    {
        $info = new PropertyRegistration();
        $info->reqmodules = array('privileges');
        $info->id   = 30046;
        $info->name = 'privilegestree';
        $info->desc = 'Dynamic PrivilegesTree';
        return $info;
    }

    function showInput($data = array())
    {
        if (isset($data['options'])) $this->options = $data['options'];

        if (!isset($data['show'])) $data['show'] = 'assigned';
	    $data['trees'] = $this->maketrees($data);

        return parent::showInput($data);
    }

// ---------------------------------------------------------------
    function addbranches($node)
    {
        $object = $node['parent'];
        $node['expanded'] = false;
        $node['selected'] = false;
        $node['children'] = array();
        foreach($this->privs->getChildren($object['pid']) as $subnode){
            $node['children'][] = $this->addbranches(array('parent'=>$subnode));
        }
        return $node;
    }

    function drawtrees($arg)
    {
        $drawntrees = array();
        foreach($this->maketrees($arg) as $tree){
            $drawntrees[] = array('tree'=>$this->drawtree($tree));
        }
        return $drawntrees;
    }

    function drawtree($node)
    {

        $this->html = "\n".'<div name="PrivilegesTree_'.$node['parent']['pid'].'" id="PrivilegesTree_'.$node['parent']['pid'].'" style="position: relative;">';
        $this->nodeindex = 0;
        $this->indent = array();
        $this->level = 0;
        $this->alreadydone = array();

        $this->drawbranch($node);
        $this->html .= "\n".'</div>'."\n";
        return $this->html;
    }

    function maketrees($args=array())
    {
        $trees = array();
        foreach ($this->privs->gettoplevelprivileges($args['show']) as $entry) {
        	$this->maketree(array('pid' => $entry['pid']));
            array_push($trees,$this->tree);
        }
        return $trees;
    }

    protected function maketree($args=array())
    {
        extract($args);
        $args['initialnode'] = array(
                    'parent' => $this->privs->getprivilegefast($pid),
                    'level' => 1
                    );
        parent::maketree($args);
    }

    private function drawbranch($node)
    {
        $this->level = $this->level + 1;
        /*if($this->level > 1){ var_dump($node);exit;}*/
        $this->nodeindex = $this->nodeindex + 1;
        $object = $node['parent'];

        // check if we've aleady processed this entry
        if (in_array($object['pid'],$this->alreadydone)) {
            $drawchildren = false;
            $node['children'] = array();
        }
        else {
            $drawchildren = true;
            array_push($this->alreadydone,$object['pid']);
        }

        // is this a branch?
        $isbranch = count($node['children'])>0 ? true : false;

        // now begin adding rows to the string
        $this->html .= "\n\t".'<div class="xar-privtree-branch" id="branch' . $this->nodeindex . '">'."\n\t\t";

        // this table holds the index, the tree drawing gifs and the info about the privilege

        // this next part holds the icon links
        // toggle the tree
        /*        if(count($this->privs->getChildren($object['pid'])) == 0) {
         $this->html .= $this->bigblank;
         }
         else {
         $this->html .= '<a href="javascript:xarTree_exec(\''. $object['name'] .'\',2);" title="Expand or collapse this tree" style="padding-left: 0.25em; padding-right: 0.25em;"><img src="modules/privileges/xarimages/toggle.gif" style="vertical-align: middle;" /></a>';
         }
        */
        // don't allow deletion of certain privileges
        if(!xarSecurityCheck('DeletePrivilege',0,'Privileges',$object['name'])) {
            $this->html .= $this->bigblank;
        }
        else {
            $this->html .= '<a href="' .
                xarModURL('privileges','admin', 'deleteprivilege',
                          array('pid'=>$object['pid'])) .
                '" title="'.xarML('Delete this Privilege').'">
                         <span style="padding-left: 0.25em; padding-right: 0.25em;">
                            <img src="modules/privileges/xarimages/delete.gif" style="vertical-align: middle;" />
                        </span>
                    </a>';
        }

        // offer to show the users/groups this privilege is assigned to
        $this->html .= '<a href="' .
            xarModURL('privileges','admin','viewroles',
                      array('pid'=>$object['pid'])) .
            '" title="'.xarML('Show the Groups/Users this Privilege is assigned to').'">
                        <span style="padding-left: 0.25em; padding-right: 0.25em;">
                            <img src="modules/privileges/xarimages/usersgroups.gif" style="vertical-align: middle;" />
                        </span>
                     </a>';

        // offer to remove this privilege from its parent
        if($object['parentid'] == 0) {
            $this->html .= $this->biggerblank;
        }
        else {
            $this->html .= '<a href="' .
                xarModURL('privileges', 'admin', 'removebranch',
                          array('childid'=> $object['pid'], 'parentid' => $object['parentid'])) .
                '" title="'.xarML('Remove this privilege from its parent').'">
                             <span style="padding-left: 0.25em; padding-right: 0.25em;">
                                 <img src="modules/privileges/xarimages/remove.gif" style="vertical-align: middle;" />
                             </span>
                         </a>'."\n\t\t";
        }

        $this->html .= $this->drawindent();
        if (count($node['children']) > 0) {
            if ($this->nodeindex != 1){
                $lastindent = array_pop($this->indent);
                if ($lastindent == $this->el) {
                    array_push($this->indent,$this->blank . $this->blank);
                }
                else {
                    array_push($this->indent,$this->aye . $this->blank);
                }
                $this->html .= $this->bar;
            }
            $this->html .= $this->expandedbox;
        }
        else {
            if ($this->nodeindex != 1){
                $this->html .= $this->bar;
            }
            $this->html .= $this->emptybox;
        }

        // draw the name of the object and make a link
        $this->html .= '<a style="padding-left: 1em" href="' .
            xarModURL('privileges', 'admin', 'modifyprivilege',
                      array('pid'=>$object['pid'])) .'" title="'.$object['description'].'">' .$object['name'] . '</a>';
        $componentcount = count($this->privs->getChildren($object['pid']));
        $this->html .= $componentcount > 0 ? "&nbsp;:&nbsp;" .$componentcount . '&nbsp;'.xarML('components') : "";
        $this->html .= "\n\t\t";
        /*        $this->html .= '<span style="position:absolute;left:35em;border: 1px dashed #f0f;">';
         $this->html .= $object['description'];
         $this->html .= "</span>";
        */
        // we've finished this row; now do the children of this privilege
        $this->html .= $isbranch ? '<div class="xar-privtree-leaf" id="leaf' . $this->nodeindex . '" >' : '';
        $ind=0;
        foreach($node['children'] as $subnode){
            $ind = $ind + 1;

            // if this is the last child, get ready to draw an "L", otherwise a sideways "T"
            if ($ind == count($node['children'])) {
                array_push($this->indent,$this->el);
            }
            else {
                array_push($this->indent,$this->tee);
            }

            // draw this child
            $this->drawbranch($subnode);

            // we're done; remove the indent string
            array_pop($this->indent);
        }
        $this->level = $this->level - 1;

        // write the closing tags
        $this->html .= $isbranch ? '</div>' : '';
        // close the html row
        $this->html .= "</div>\n";

    }
}

?>
