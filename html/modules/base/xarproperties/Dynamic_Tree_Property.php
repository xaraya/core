<?php
/**
 *
 * Property menu
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
    protected $options;

    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'base';
        $this->filepath   = 'modules/base/xarproperties';
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
}
?>
