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

sys::import('xaraya.exceptions.legacy.exceptionrendering');
class TextExceptionRendering extends ExceptionRendering
{
    public $linebreak = "\n";
    public $openstrong = "";
    public $closestrong = "";
    public $openpre = "";
    public $closepre = "";
}

?>
