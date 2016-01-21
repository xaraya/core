<?php
/**
 * @package themes
 * @subpackage default theme
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Marc Lutolf <mfl@netspan.ch>
 */

sys::import('modules.themes.class.interfaces');

class ThemeInit implements iThemeInit
{
    public function init(Array $data=array())
    {
        $dat_file = 'themes/' . $data['name'] . '/configuration.xml';
        $data = array('file' => $dat_file);
        try {
            $objectid = xarMod::apiFunc('dynamicdata','util','import', $data);
        } catch (Exception $e) {
            //
        }
        return true;
    }

}

?>