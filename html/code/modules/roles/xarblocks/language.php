<?php
/**
 * Language Selection via block
 *
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/27.html
 */

/*
 * Language Selection via block
 * @author Marco Canini
 * initialise block
 */
sys::import('xaraya.structures.containers.blocks.basicblock');

class Roles_LanguageBlock extends BasicBlock
{
    protected $type                = 'language';
    protected $module              = 'roles';
    protected $text_type           = 'Language';
    protected $text_type_long      = 'Language selection';

    function display()
    {
        // if (xarMLSGetMode() != XARMLS_BOXED_MULTI_LANGUAGE_MODE) {
        if (xarMLSGetMode() == XARMLS_SINGLE_LANGUAGE_MODE) return;

        $current_locale = xarUserGetNavigationLocale();

        $site_locales = xarMLSListSiteLocales();

        asort($site_locales);
        if (count($site_locales) <= 1) return;

        foreach ($site_locales as $locale) {
            $locale_data =& xarMLSLoadLocaleData($locale);

            $selected = ($current_locale == $locale);

            $locales[] = array(
                'locale'   => $locale,
                'country'  => $locale_data['/country/display'],
                'name'     => $locale_data['/language/display'],
                'selected' => $selected
            );
        }

        $data['form_action'] = xarModURL('roles', 'user', 'changelanguage');
        $data['form_picker_name'] = 'locale';
        $data['locales'] = $locales;

        if (xarServer::getVar('REQUEST_METHOD') == 'GET') {
            // URL of this page
            $data['return_url'] = xarServer::getCurrentURL();
        } else {
            // Base URL of the site
            $data['return_url'] = xarServer::getBaseURL();
        }
        return $data;
    }
}

?>