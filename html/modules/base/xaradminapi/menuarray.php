<?php
/**
 * Utility function pass individual menu items to the main menu
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * utility function to create an array for a getmenulinks function
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @returns array
 * @return array of menulinks for a module
 */
function base_adminapi_menuarray()
{
    $urlinfo = xarRequestGetInfo();
    $tabs = xarModAPIFunc($urlinfo[0],'data','adminmenu');
    $menulinks = array();
	foreach($tabs as $tab) {
		$url = isset($tab['target']) ? xarModURL($urlinfo[0],'admin',$tab['target']) : xarServerGetBaseURL();
		$label = isset($tab['label']) ? $tab['label'] : xarML('Missing label');
		$title = isset($tab['title']) ? $tab['title'] : $label;
		$link = array('url'   => $url,
							  'title' => $title,
							  'label' => $label
					   );
		if(isset($tab['mask'])) {
		    if (xarSecurityCheck($tab['mask'],0)) {
				$menulinks[] = $link;
		    }
		} else {
			$menulinks[] = $link;
		}
	}
    return $menulinks;
}

?>
