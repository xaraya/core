<?php

sys::import('xaraya.mapper.routers.routes.static');

class StaticRoute extends xarRoute
{
    public function match(xarRequest $request, $partial=false)
    {
        $path = $request->getURL();
        $match = trim($path, $this->delimiter) == $this->route;
        if ($match) return $this->defaults;
        return false;
    }
}
?>