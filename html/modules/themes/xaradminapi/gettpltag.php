<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 */
/**
 * Get registered template tags
 *
 * @param none
 * @returns array
 * @return array of themes in the database
 * @Author Simon Wunderlin <sw@telemedia.ch>
 */
function themes_adminapi_gettpltag($args)
{
    extract($args);
    if (!isset($tagname)) return;
    
    $aData = array(
        'tagname'       => '',
        'module'        => '',
        'handler'       => '',
        'attributes'    => array(),
        'num_atributes' => 0
    );

    if (trim($tagname) != '') {
        $oTag = xarTplGetTagObjectFromName($tagname);
        $aData = array(
            'tagname'       => $oTag->getName(),
            'module'        => $oTag->getModule(),
            'handler'       => $oTag->getHandler(),
            'attributes'    => $oTag->getAttributes(),
            'num_atributes' => sizeOf($oTag->getAttributes())
        );
        
    }
    $aData['max_attrs'] = 10;
    
    return $aData;
}

?>