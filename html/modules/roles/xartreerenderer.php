<?php
/**
 * File: $Id$
 *
 * Purpose of file:  Roles tree renderer
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage Privileges Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
*/

include_once 'modules/roles/xarroles.php';

class xarTreeRenderer {

    var $roles;

    // some variables we'll need to hold drawing info
    var $html;
    var $nodeindex;
    var $indent;
    var $level;

    // convenience variables to hold strings referring to pictures
    var $el = '<img src="modules/roles/xarimages/el.gif" alt="" style="vertical-align: middle"/>';
    var $tee = '<img src="modules/roles/xarimages/T.gif" alt="" style="vertical-align: middle"/>';
    var $aye = '<img src="modules/roles/xarimages/I.gif" alt="" style="vertical-align: middle"/>';
    var $bar = '<img src="modules/roles/xarimages/s.gif" alt="" style="vertical-align: middle"/>';
    var $emptybox = '<img class="box" src="modules/roles/xarimages/k1.gif" alt="" style="vertical-align: middle"/>';
    var $expandedbox = '<img class="box" src="modules/roles/xarimages/k2.gif" alt="" style="vertical-align: middle"/>';
    var $collapsedbox = '<img class="box" src="modules/roles/xarimages/k3.gif" alt="" style="vertical-align: middle"/>';
    var $blank = '<img src="modules/privileges/xarimages/blank.gif" alt="" style="vertical-align: middle"/>';
    var $bigblank ='<span style="padding-left: 0.25em; padding-right: 0.25em;"><img src="modules/privileges/xarimages/blank.gif" alt="" style="vertical-align: middle; width: 16px; height: 16px;" /></span>';
    var $smallblank ='<span style="padding-left: 0em; padding-right: 0em;"><img src="modules/privileges/xarimages/blank.gif" alt="" style="vertical-align: middle; width: 1em; height: 16px;" /></span>';

    // we'll use this to check whether a group has already been processed
    var $alreadydone;

    /**
     * Constructor
     *
    */
        function xarTreeRenderer() {
            $this->roles = new xarRoles();
        }

    /**
     * maketree: make a tree of the roles that are groups
     *
     * We don't include users in the tree because there are too many to display
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  private
     * @param   none
     * @return  boolean
     * @throws  none
     * @todo    none
    */
        function maketree() {
            return $this->addbranches(array('parent'=>$this->roles->getgroup(1)));
        }

    /**
     * addbranches: given an initial tree node, add on the brtanches that are groups
     *
     * We don't include users in the tree because there are too many to display
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  private
     * @param   tree node
     * @return  tree node
     * @throws  none
     * @todo    none
    */
        function addbranches($node){
            $object = $node['parent'];
            $node['children'] = array();
            foreach($this->roles->getsubgroups($object['uid']) as $subnode){
                $node['children'][] = $this->addbranches(array('parent'=>$subnode));
            }
            return $node;
        }

    /**
     * drawtree: create a crude html drawing of the role tree
     *
     * We use the data from maketree to create a tree layout
     * This should be in a template or at least in the xaradmin file, but it's easier here
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  private
     * @param   array representing an initial node
     * @return  none
     * @throws  none
     * @todo    none
    */

    /**
     * drawtree: draws the role tree
     * sets everything up and draws the first node
     *     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  private
     * @param   array representing a tree node
     * @return  none
     * @throws  none
     * @todo    none
    */

    function drawtree($node) {

        $this->html = '<div name="RolesTree" id="RolesTree">';
        $this->nodeindex = 0;
        $this->indent = array();
        $this->level = 0;
        $this->alreadydone = array();

        $this->drawbranch($node);
        $this->html .= '</div>';
        return $this->html;
    }

    /**
     * drawbranch: draw a branch of the role tree
     *
     * This is a recursive function
     * This should be in a template or at least in the xaradmin file, but it's easier here
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  private
     * @param   array representing a tree node
     * @return  none
     * @throws  none
     * @todo    none
    */

    function drawbranch($node){

        $this->level = $this->level + 1;
        $this->nodeindex = $this->nodeindex + 1;
        $object = $node['parent'];

    // check if we've aleady processed this entry
        if (in_array($object['uid'],$this->alreadydone)) {
            $drawchildren = false;
            $node['children'] = array();
        }
        else {
            $drawchildren = true;
            $this->alreadydone[] = $object['uid'];
        }

    // is this a branch?
        $isbranch = count($node['children'])>0 ? true : false;

    // now begin adding rows to the string
        $this->html .= '<div class="xarbranch" id="branch' . $this->nodeindex . '">';

    // this next table holds the Delete, Users and Privileges links
    // don't allow deletion of certain roles
        if(($object['uid'] <= xarModGetVar('roles','frozenroles')) || ($object['users'] > 0) || (!$drawchildren)) {
            $this->html .= $this->bigblank;
        }
        else {
            $this->html .= '<a href="' .
                xarModURL('roles',
                     'admin',
                     'deleterole',
                     array('uid'=>$object['uid'])) .
                     '" title="Delete this Group" style="padding-left: 0.25em; padding-right: 0.25em;"><img src="modules/roles/xarimages/delete.gif" style="vertical-align: middle;" /></a>';
        }

    // offer to show users of a group if there are some
        if($object['users'] == 0 || (!$drawchildren)) {
            $this->html .= $this->bigblank;
        }
        else {
            $this->html .= '<a href="' .
                    xarModURL('roles',
                         'admin',
                         'showusers',
                         array('uid'=>$object['uid'])) .
                         '" title="Show the Users in this Group" style="padding-left: 0.25em; padding-right: 0.25em;"><img src="modules/roles/xarimages/users.gif" style="vertical-align: middle;" /></a>';
        }

    // link to group email
        if($object['users'] == 0 || (!$drawchildren)) {
            $this->html .= $this->bigblank;
        }
        else {
            $this->html .= '<a href="' .
                    xarModURL('roles',
                         'admin',
                         'createmail',
                         array('uid'=>$object['uid'])) .
                         '" title="Email the Users in this Group" style="padding-left: 0.25em; padding-right: 0.25em;"><img src="modules/roles/xarimages/email.gif" style="vertical-align: middle;" /></a>';
        }

    // offer to show the privileges of this group
        if(!$drawchildren) {
            $this->html .= $this->bigblank;
        }
        else {
            $this->html .= '<a href="' .
                xarModURL('roles',
                     'admin',
                     'showprivileges',
                     array('uid'=>$object['uid'])) .
                     '" title="Show the Privileges assigned to this Group" style="padding-left: 0.25em; padding-right: 0.25em;"><img src="modules/roles/xarimages/privileges.gif" style="vertical-align: middle;" /></a>';
        }

    // offer to test the privileges of this group
        if(!$drawchildren) {
            $this->html .= $this->bigblank;
        }
        else {
            $this->html .= '<a href="' .
                xarModURL('roles',
                     'admin',
                     'testprivileges',
                     array('uid'=>$object['uid'])) .
                     '" title="Test this Groups\'s Privileges" style="padding-left: 0.25em; padding-right: 0.25em;"><img src="modules/roles/xarimages/test.gif" style="vertical-align: middle;" /></a>';
        }
        $this->html .= $this->smallblank;

    // this table hold the index, the tree drawing gifs and the info about the role
        $this->html .= $this->drawindent();
        if ($isbranch) {
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
            $this->html .= $this->bar;
            $this->html .= $this->emptybox;
        }
        $this->html .=  '<span style="padding-left: 1em">';

    // if we've already done this entry skip the links and just tell the user
        if (!$drawchildren) {
            $this->html .= '<b>' . $object['name'] . '</b>: ';
            $this->html .= ' see the entry above';
        }
        else{
            $this->html .= '<a href="' .
                        xarModURL('roles',
                             'admin',
                             'modifyrole',
                             array('uid'=>$object['uid'])) .' ">' .$object['name'] . '</a>: &nbsp;';
            $this->html .= count($this->roles->getsubgroups($object['uid'])) . ' subgroups';
            $this->html .= ' | ' . $object['users'] . ' users</span>';
        }


    // we've finished this row; now do the children of this role
        $this->html .= $isbranch ? '<div class="xarleaf" id="leaf' . $this->nodeindex . '" >' : '';
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
        $this->html .= '</div>';

    }

    /**
     * drawindent: draws the graphic part of the tree
     *
     * A helper funtion to output a HTML string containing the pictures for
     * a line of the tree
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   none
     * @return  string
     * @throws  none
     * @todo    none
    */

    function drawindent() {
        $html = '';
        foreach ($this->indent as $column) {$html .= $column;}
        return $html;
    }
}

?>