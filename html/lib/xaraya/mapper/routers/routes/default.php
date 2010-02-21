<?php
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
/*    
    public function encode($data=array(), $reset=false, $encode=true, $partial=false)
    {
        if (!$this->keysSet) $this->setRequestKeys();
        $params = (!$reset) ? $this->parts : array();

        foreach ($data as $key => $value) {
            if ($value !== null) {
                $params[$key] = $value;
            } elseif (isset($params[$key])) {
                unset($params[$key]);
            }
        }
        $params += $this->defaults;

        $url = '';
        
        // Do module, type, func in that order
        if ($encode) $params['module'] = urlencode($params['module']);        
        $url .= 'module=' . $params['module'];
        unset($params['module']);
        if ($encode) $params['type'] = urlencode($params['type']);        
        $url .= '&type=' . $params['type'];
        unset($params['type']);
        if ($encode) $params['func'] = urlencode($params['func']);        
        $url .= '&func=' . $params['func'];
        unset($params['func']);
        
        // Do the rest of the URL parameters
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    if ($encode) $v = urlencode($v);
                    $url .= '&' . $k;
                    $url .= '=' . $v;
                }
            } else {
                if ($encode) $value = urlencode($value);
                $url .= '&' . $key;
                $url .= '=' . $value;
            }
        }
        return $url;
    }
    */
}
?>