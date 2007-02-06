<?php
/**
 * Exception Handling System
 *
 * @package exceptions
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */

/**
 * Base Exception class
 *
 * @package exceptions
 */
class xarException extends Object
{
    public $msg = '';
    public $id = 0;
    public $major = 0;
    public $defaults;
    public $title = '';
    public $short = '';
    public $long = '';
    public $hint = '';
    public $stack;
    public $product = '';
    public $component = '';
    public $module = '';

    function xarException()
    {
        $this->stack = array();
    }

    function __toString()
    {
        return "code: " . $this->major . " " . $this->id . " | " . $this->msg;
    }

    function getType()
    {
        return get_class($this);
    }

    function toHTML()
    {
        return nl2br(htmlspecialchars($this->msg)) . '<br/>';
    }

    function getID()
    {
        return $this->id;
    }

    function getMajor()
    {
        return $this->major;
    }

    function getTitle()
    {
        return $this->title;
    }

    function getShort()
    {
        if ($this->msg != '' && $this->msg != 'Default msg') return $this->msg;
        else return $this->short;
    }

    function getLong()
    {
        return $this->long;
    }

    function getHint()
    {
        return $this->hint;
    }

    function getStack()
    {
        return $this->stack;
    }

    function getProduct()
    {
        return $this->product;
    }

    function getComponent()
    {
        return $this->component;
    }

    function setID($id)
    {
        $this->id = $id;
    }

    function setMajor($id)
    {
        $this->major = $id;
    }

    function setTitle($id)
    {
        $this->title = $id;
    }

    function setShort($id)
    {
        $this->short = $id;
    }

    function setLong($id)
    {
        $this->long = $id;
    }

    function setHint($id)
    {
        $this->hint = $id;
    }

    function setMsg($id)
    {
        $this->msg = $id;
    }

    function setStack($stk)
    {
        $this->stack = $stk;
    }

    function setProduct($x)
    {
        $this->product = $x;
    }

    function setComponent($x)
    {
        $this->component = $x;
    }
}

?>
