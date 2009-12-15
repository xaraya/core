<?php
sys::import('xaraya.mapper.routers.routes.base');

class HostnameRoute extends xarRoute
{
    protected $protocol;
    protected $requirements;
    
    public function loadroute($route=null, $defaults=array(), $reqs=array(), $protocol=null)
    {
        $route                = trim($route, '.');
        $this->_requirements  = (array) $reqs;
        $this->protocol       = $protocol;

        if ($route != '') {
            foreach (explode('.', $route) as $pos => $part) {
                if (substr($part, 0, 1) == $this->_hostVariable) {
                    $name = substr($part, 1);
                    $this->parts[$pos] = (isset($reqs[$name]) ? $reqs[$name] : $this->_defaultRegex);
                    $this->_variables[$pos] = $name;
                } else {
                    $this->parts[$pos] = $part;
                    $this->_staticCount++;
                }
            }
        }
    }

    public function match(xarRequest $request, $partial=false)
    {
        if ($this->protocol !== null) {
            if ($request->getProtocol() !== $this->protocol) return false;
        }

        // Get the host and remove unnecessary port information
        $host = $request->getHost();
        if (preg_match('#:\d+$#', $host, $result) === 1) {
            $host = substr($host, 0, -strlen($result[0]));
        }

        $hostStaticCount = 0;
        $values = array();

        $host = trim($host, '.');

        if ($host != '') {
            $host = explode('.', $host);

            foreach ($host as $pos => $hostPart) {
                // Host is longer than a route, it's not a match
                if (!array_key_exists($pos, $this->parts)) {
                    return false;
                }

                $name = isset($this->_variables[$pos]) ? $this->_variables[$pos] : null;
                $hostPart = urldecode($hostPart);

                // If it's a static part, match directly
                if ($name === null && $this->parts[$pos] != $hostPart) {
                    return false;
                }

                // If it's a variable with requirement, match a regex. If not - everything matches
                if ($this->parts[$pos] !== null && !preg_match($this->_regexDelimiter . '^' . $this->parts[$pos] . '$' . $this->_regexDelimiter . 'iu', $hostPart)) {
                    return false;
                }

                // If it's a variable store it's value for later
                if ($name !== null) {
                    $values[$name] = $hostPart;
                } else {
                    $hostStaticCount++;
                }
            }
        }

        // Check if all static mappings have been matched
        if ($this->_staticCount != $hostStaticCount) {
            return false;
        }

        $return = $values + $this->_defaults;

        // Check if all map variables have been initialized
        foreach ($this->_variables as $var) {
            if (!array_key_exists($var, $return)) {
                return false;
            }
        }

        $this->_values = $values;

        return $return;

    }

    public function encode($data=array(), $reset=false, $encode=true, $partial=false)
    {
        $host = array();
        $flag = false;

        foreach ($this->parts as $key => $part) {
            $name = isset($this->_variables[$key]) ? $this->_variables[$key] : null;

            $useDefault = false;
            if (isset($name) && array_key_exists($name, $data) && $data[$name] === null) {
                $useDefault = true;
            }

            if (isset($name)) {
                if (isset($data[$name]) && !$useDefault) {
                    $host[$key] = $data[$name];
                    unset($data[$name]);
                } elseif (!$reset && !$useDefault && isset($this->_values[$name])) {
                    $host[$key] = $this->_values[$name];
                } elseif (isset($this->_defaults[$name])) {
                    $host[$key] = $this->_defaults[$name];
                } else {
                    throw new Exception($name . ' is not specified');
                }
            } else {
                $host[$key] = $part;
            }
        }

        $return = '';

        foreach (array_reverse($host, true) as $key => $value) {
            if ($flag || !isset($this->_variables[$key]) || $value !== $this->getDefault($this->_variables[$key]) || $partial) {
                if ($encode) $value = urlencode($value);
                $return = '.' . $value . $return;
                $flag = true;
            }
        }

        $url = trim($return, '.');

        if ($this->protocol !== null) {
            $protocol = $this->protocol;
        } else {
            $request = $this->getRequest();
            $protocol = $request->getProtocol();
        }

        $hostname = implode('.', $host);
        $url      = $protocol . '://' . $url;

        return $url;
    }
}
?>