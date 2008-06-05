<?php
/**
 * CurrentUrlEntityNode
 *
 * wraps xarServer::getCurrentUrl()
 *
 * @package blocklayout
 * @access private
 */
class CurrenturlEntityNode extends EntityNode
{
    function render()
   {
       return "xarServer::getCurrentUrl()";
   }

}
?>