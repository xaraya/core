<?php

/**
* xarTpl__XarBaseUrlEntityNode
 *
 * wraps xarServerGetBaseURL()
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarBaseurlEntityNode extends xarTpl__EntityNode
{
    function render()
    {
        return "xarServerGetBaseURL()";
    }
}
?>