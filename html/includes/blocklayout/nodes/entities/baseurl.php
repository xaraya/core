<?php

/**
* xarTpl__XarBaseUrlEntityNode
 *
 * wraps xarServer::getBaseURL()
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarBaseurlEntityNode extends xarTpl__EntityNode
{
    function render()
    {
        return "xarServer::getBaseURL()";
    }
}
?>