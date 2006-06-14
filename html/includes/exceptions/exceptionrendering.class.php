<?php
/**
 *
 * Exception Handling System
 *
 * @package exceptions
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */

class ExceptionRendering
{
    public $exception;
    public $id;
    public $major;
    public $type;
    public $defaults;
    public $title;
    public $short;
    public $long;
    public $hint;
    public $stack;
    public $linebreak = "<br/>";
    public $openstrong = "<strong>";
    public $closestrong = "</strong>";
    public $openpre = "<pre>";
    public $closepre = "</pre>";

    function ExceptionRendering($exception = NULL)
    {
        $this->exception = $exception;
        $this->id = $exception->getID();
        $this->major = $exception->getMajor();
        switch ($this->major) {
            case XAR_SYSTEM_EXCEPTION:
                include(dirname(__FILE__) . "/systemexception.defaults.php");
                if (!isset($this->defaults[$this->id])) {
                    $this->id = "EXCEPTION_FAILURE";
                }
                $this->load();
                $this->type = 'System Error';
                break;
            case XAR_USER_EXCEPTION:
                include(dirname(__FILE__) . "/defaultuserexception.defaults.php");
                if (isset($this->defaults[$this->id])) {
                    $this->load();
                }
                else {
                    $this->title = $this->id;
                    $this->short = xarML("No further information available");
                    $this->long = "";
                    $this->hint = "";
                }
                $this->type = 'User Error';
                break;
            case XAR_SYSTEM_MESSAGE:
                include(dirname(__FILE__) . "/systemmessage.defaults.php");
                if (isset($this->defaults[$this->id])) {
                    $this->load();
                }
                else {
                    $this->title = $this->id;
                    $this->short = xarML("No further information available");
                    $this->long = "";
                    $this->hint = "";
                }
                $this->type = 'System Message';
                break;
            default:
                include(dirname(__FILE__) . "/systemexception.defaults.php");
                break;
        }
        $this->defaults = '';
    }

    function load()
    {
        $id = $this->id;
        $this->title = isset($this->defaults[$id]['title']) ? $this->defaults[$id]['title'] : '';
        $this->short = isset($this->defaults[$id]['short']) ? $this->defaults[$id]['short'] : '';
        $this->long = isset($this->defaults[$id]['long'])   ? $this->defaults[$id]['long'] : '';
        $this->hint = isset( $this->defaults[$id]['hint'])  ? $this->defaults[$id]['hint'] : '';
    }

    function getMajor()
    {
        return $this->major;
    }

    function getType()
    {
        return $this->type;
    }

    function getTitle()
    {
        return $this->exception->getTitle() == '' ? $this->title : $this->exception->getTitle();
    }

    function getLong()
    {
        return $this->exception->getLong() == '' ? $this->long : $this->exception->getLong();
    }

    function getHint()
    {
        return $this->exception->getHint() == '' ? $this->hint : $this->exception->getHint();
    }

    function getShort()
    {
        return $this->exception->getShort() == '' ? $this->short : $this->exception->getShort();
    }

    function getProduct()
    {
        return $this->exception->getProduct();
    }

    function getComponent()
    {
        return $this->exception->getComponent();
    }

    // FIXME: This method doesnt belong here, the dependencies should give a hint why not
    function isadmin()
    {
        // Dependency (roles module)
        if (!class_exists("xarRoles"))
            return false;
        
        if(!xarCore::getCached('installer','installing')) {
            // Dependency!
            $roles = new xarRoles();
            $admins = "Administrators";
            // Dependency!
            $admingroup = $roles->findRole("Administrators");
            // Dependency! (session)
            $me = $roles->getRole(xarSessionGetVar('uid'));
            if (!empty($admingroup) && isset($me)) {
                // Dependency!
                return $me->isParent($admingroup);
            } else {
                return false;
            }
        }
        else return true;
    }

    function iserrorcollection()
    {
        return get_class($this->exception) == 'errorcollection';
    }

    function getID()
    {
        if ($this->iserrorcollection()) $this->id = "PHP_ERROR";
        else $this->id = $this->exception->getID();
    }

    function getMsg()
    {
        if ($this->iserrorcollection()) {
          $collection = $this->exception->exceptions;
          $message = "One or more PHP errors were encountered." . $this->linebreak . $this->linebreak;
          foreach($collection as $collecteditem) {
              $message .= $collecteditem['id'] . $this->linebreak;
              // QUESTION: does the htmlspecialchars belong here?
              $message .= htmlspecialchars($collecteditem['value']->msg) . $this->linebreak;
          }
          return $message;
        }
        else return $this->exception->getMsg();
    }

    function getStack()
    {
        $showParams = xarCoreIsDebugFlagSet(XARDBG_SHOW_PARAMS_IN_BT);

        if ($this->exception->getMajor() != XAR_USER_EXCEPTION && $this->isadmin()) {
            $stack = $this->exception->getStack();
            $text = "";
            for ($i = 2, $j = 1, $max = count($stack); $i < $max; $i++, $j++) {
                if (isset($stack[$i]['function'])) $function = $stack[$i]['function'];
                else $function = '{}';
                // FIXME: guess ;-)
                $text .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;at ' . $this->openstrong .$function.'(';
                // Note: eval() doesn't generate file or line
                if (isset($stack[$j]['file'])) $text .= basename(strval($stack[$j]['file'])).':';
                if (isset($stack[$j]['line'])) $text .= $stack[$j]['line'];
                $text .= ')' . $this->closestrong . $this->linebreak;
                if ($showParams && isset($stack[$i]['args']) && is_array($stack[$i]['args']) && count($stack[$i]['args']) > 0) {
                    ob_start();
                    print_r($stack[$i]['args']);
                    $dump = ob_get_contents();
                    ob_end_clean();
                    // FIXME: guess ;-)
                    $text .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $this->openpre . htmlspecialchars($dump) . $this->closepre;
                    $text .= $this->linebreak;
                }
            }
            return $text;
        }
        else return "";
    }
}
?>
