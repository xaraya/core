<?php

/**
* xarTpl__XarCurrentUrlEntityNode
 *
 * wraps xarServerGetCurrentURL()
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarCurrenturlEntityNode extends xarTpl__EntityNode
{
    function render()
   {
       return "xarServerGetCurrentURL()";
   }

}
?>