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

include_once "includes/exceptions/exceptionrendering.class.php";

class TextExceptionRendering extends ExceptionRendering
{
    function TextExceptionRendering() {
        ExceptionRendering();
        $this->linebreak = "\n";
    }
}

?>