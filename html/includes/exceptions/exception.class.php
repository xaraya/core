<?php
/**
 * File: $Id$
 *
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
class Exception
{
    var $msg = '';
    var $id = 0;
    var $major = 0;
    var $defaults;
    var $title = '';
    var $short = '';
    var $long = '';
    var $hint= '';
    var $stack;

    function Exception() {
    }

    function toString() {
        return "code: " . $this->major . " " . $this->id . " | " . $this->msg;
    }

    function getType() { return get_class($this); }
    function toHTML() { return nl2br(xarVarPrepForDisplay($this->msg)) . '<br/>'; }
    function getID() { return $this->id; }
    function getMajor() { return $this->major; }
    function getTitle() { return $this->title; }
    function getShort() {
        if ($this->msg != '' && $this->msg != 'Default msg') return $this->msg;
        else return $this->short;
    }
    function getLong() { return $this->long; }
    function getHint() { return $this->hint; }
    function getStack() { return $this->stack; }

    function setID($id) { $this->id = $id; }
    function setTitle($id) { $this->title = $id; }
    function setShort($id) { $this->short = $id; }
    function setLong($id) { $this->long = $id; }
    function setHint($id) { $this->hint = $id; }
    function setMsg($id) { $this->msg = $id; }
    function setStack($stk) { $this->stack = $stk; }
}

?>