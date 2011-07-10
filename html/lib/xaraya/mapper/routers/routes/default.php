<?php
/**
 * Default Route class
 *
 * @package core
 * @subpackage controllers
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @author Marc Lutolf <mfl@netspan.ch>
**/

sys::import('xaraya.mapper.routers.routes.base');

class DefaultRoute extends xarRoute
{
    public function __construct(Array $defaults=array(), xarDispatcher $dispatcher=null)
    {
        $this->defaults += array(
                            'module' => 'base',
                            'type'   => 'user',
                            'func'   => 'main',
                                );
        parent::__construct($defaults, $dispatcher);
    }

    public function match(xarRequest $request, $partial=false)
    {
        $this->setRequestKeys();

        $path = $request->getURL();
        $urlparts = parse_url($path);
        $params = array();
        if (empty($urlparts['query'])) return false;
        //Note that the explode depends on  &amp;
        $pairs = explode('&amp;', $urlparts['query']);
        foreach($pairs as $pair) {
            if (trim($pair) == '') continue;
            $parts = explode('=', $pair);
            if (empty($parts[1])) return false;
            $params[$parts[0]] = urldecode($parts[1]);
        }
        if (empty($params['module'])) return false;
        
        $this->parts['module'] = $params['module'];
        unset($params['module']);
        if (empty($params['type'])) $params['type'] = 'user';
        $this->parts['type'] = $params['type'];
        unset($params['type']);
        if (empty($params['func'])) $params['func'] = 'main';
        $this->parts['func'] = $params['func'];
        unset($params['func']);
        $this->parts['params'] = $params;
        
        $request->setRoute('default');
        return true;
    }
}
?>