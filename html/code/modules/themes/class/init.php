<?php

/**
 * @package modules\themes
 * @subpackage themes
 * @copyright see the html/credits.html file in this release
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/70.html
 *
 * @author Marc Lutolf <mfl@netspan.ch>
 */

sys::import('modules.themes.class.interfaces');
use Xaraya\Services\xar;

class ThemeInit implements iThemeInit
{
    public function init(array $data = [])
    {
        $dat_file = 'themes/' . $data['name'] . '/configuration.xml';
        $data = ['file' => $dat_file];
        try {
            $objectid = xar::mod()->apiFunc('dynamicdata', 'util', 'import', $data);
        } catch (Exception $e) {
            //
        }
        return true;
    }

}
