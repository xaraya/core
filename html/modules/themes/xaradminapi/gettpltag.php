<?php

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
