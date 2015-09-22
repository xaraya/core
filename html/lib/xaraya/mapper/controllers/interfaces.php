<?php
/**
 * Controller Interface class
 *
 * @package core
 * @subpackage controllers
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Marc Lutolf <mfl@netspan.ch>
**/

interface iController
{
    public function __construct(xarRequest $request=null);
    public function decode(Array $data=array());
    public function encode(xarRequest $request);
    public function getActionString(xarRequest $request);  
    public function getInitialPath(xarRequest $request);
}

?>