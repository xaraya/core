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

include_once dirname(__FILE__)."/exception.class.php";

class DefaultUserException extends xarException
{
    public $link;

    function DefaultUserException($msg = '', $link = NULL)
    {
        parent::xarException();
        $this->msg = $msg;
        $this->link = $link;
    }

    function load($id) 
    {
        if (array_key_exists($id, $this->defaults)) parent::load($id);
        else {
            $this->title = $id;
            $this->short = "No further information available";
        }
    }

    function toHTML()
    {
        $str = "<pre>\n" . htmlspecialchars($this->msg) . "\n</pre><br/>";
        if ($this->link) {
            $str .= '<a href="'.$this->link[1].'">'.$this->link[0].'</a><br/>';
        }
        return $str;
    }

}

?>
