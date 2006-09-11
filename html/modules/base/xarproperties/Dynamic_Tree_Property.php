<?php
/**
 *
 * Tree Property
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2006 by to be added
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link to be added
 * @subpackage Base Module
 * @author Marc Lutolf <mfl@netspan.ch>
 *
 */

class Dynamic_Tree_Property extends Dynamic_Property
{
    public $tree;

    protected $options;

    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'base';
        $this->filepath   = 'modules/base/xarproperties';
        $this->template = $this->getTemplate();
        $this->options = array();
    }

    static function getRegistrationInfo()
    {
        $info = new PropertyRegistration();
        $info->reqmodules = array('base');
        $info->id   = 30044;
        $info->name = 'tree';
        $info->desc = 'Dynamic Tree';
        return $info;
    }

    function toArray() {
    	return $this->tree;
    }

    function showInput($data = array())
    {
        if (isset($data['options'])) $this->options = $data['options'];
        return parent::showInput($data);
    }

    function showOutput($data = array())
    {
        if (isset($data['options'])) $this->options = $data['options'];
        return parent::showOutput($data);
    }

    protected function maketree($args=array())
    {
        extract($args);
        if (isset($levels)) $this->levels = $levels;
        $this->tree = $this->addbranches($initialnode);
        /*
        $newtree = new ArrayObject($this->tree);
        $iterator = $newtree->getiterator();
        foreach ( $iterator as $current ) {
            var_dump(1);
        }
        */
    }

}
?>
