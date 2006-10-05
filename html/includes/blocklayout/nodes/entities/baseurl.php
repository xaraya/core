<?php

/**
 * BaseUrlEntityNode
 *
 * wraps xarServer::getBaseURL()
 *
 * @package blocklayout
 * @access private
 */
class BaseUrlEntityNode extends EntityNode
{
    function render()
    {
        return "xarServer::getBaseURL()";
    }
}
?>