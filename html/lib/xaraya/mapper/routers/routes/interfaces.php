<?php
/**
 * Route Interface class
 *
 * @package core\controllers
 * @subpackage controllers
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Marc Lutolf <mfl@netspan.ch>
**/

interface iRoute
{
    public function __construct(Array $defaults=array(), xarDispatcher $dispatcher=null);
    public function match(xarRequest $request, $partial=false);
}

?>