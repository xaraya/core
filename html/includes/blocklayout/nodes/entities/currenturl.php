<?php

/**
* xarTpl__XarCurrentUrlEntityNode
 *
 * wraps xarServer::getCurrentUrl()
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarCurrenturlEntityNode extends xarTpl__EntityNode
{
    function render()
   {
       return "xarServer::getCurrentUrl()";
   }

}
?>