<?php

/**
 * Language Selection via block
 *
 * @package modules\roles
 * @subpackage roles
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/27.html
 */

/*
 * Language Selection via block
 * @author Marco Canini
 * initialise block
 */
sys::import('xaraya.structures.containers.blocks.basicblock');

/**
 * Roles Language Block
 */
class Roles_LanguageBlock extends BasicBlock
{
    protected $type                = 'language';
    protected $module              = 'roles';
    protected $text_type           = 'Language';
    protected $text_type_long      = 'Language selection';

    /**
     * Display the language block
     * @return array<mixed>|void Display data array
     */
    public function display()
    {
        // if (xarMLS::getMode() != xarMLS::BOXED_MULTI_LANGUAGE_MODE) {
        if (xarMLS::getMode() == xarMLS::SINGLE_LANGUAGE_MODE) {
            return;
        }

        $current_locale = $this->user()->getLocale();

        $site_locales = xarMLS::listSiteLocales();

        asort($site_locales);
        if (count($site_locales) <= 1) {
            return;
        }

        foreach ($site_locales as $locale) {
            $locale_data = $this->mls()->loadLocale($locale);

            $selected = ($current_locale == $locale);

            $locales[] = [
                'locale'   => $locale,
                'country'  => $locale_data['/country/display'],
                'name'     => $locale_data['/language/display'],
                'selected' => $selected,
            ];
        }

        $data['form_action'] = $this->ctl()->getModuleURL('roles', 'user', 'changelanguage');
        $data['form_picker_name'] = 'locale';
        $data['locales'] = $locales;

        if ($this->ctl()->getRequestMethod() == 'GET') {
            // URL of this page
            $data['return_url'] = $this->ctl()->getCurrentURL();
        } else {
            // Base URL of the site
            $data['return_url'] = $this->ctl()->getBaseURL();
        }
        return $data;
    }
}
