<?php

sys::import('xaraya.structures.events.observer');

abstract class HookObserver extends EventObserver implements ixarEventObserver
{
    public $module = "modules";
}
?>
