<?php

/**
 * @package modules\installer
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Installer\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Installer\AdminGui;
use Exception;
use xarLocale;
use xarMLS;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * installer admin phase1 function
 * @extends MethodClass<AdminGui>
 */
class Phase1Method extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Phase 1: Welcome (Set Language and Locale) Page
     * @access private
     * @return array data for the template display
     * @see AdminGui::phase1()
     */
    public function __invoke(array $args = [])
    {
        if (!file_exists('install.php')) {
            throw new Exception('Already installed');
        }
        xarVar::fetch('install_language', 'str::', $install_language, 'en_US.utf-8', xarVar::NOT_REQUIRED);

        // Get the installed locales
        $locales = xarMLS::listSiteLocales();

        // Construct the array for the selectbox (iso3code, string in own locale)
        if (!empty($locales)) {
            $languages = [];
            foreach ($locales as $locale) {
                // Get the isocode and the description
                // Before we load the locale data, let's check if the locale is there

                // <marco> This check is really not necessary since available locales are
                // already determined from existing files. The relative code is in install.php
                //$fileName = sys::varpath() . "/locales/$locale/locale.xml";
                //if(file_exists($fileName)) {
                $locale_data = xarLocale::loadData($locale);
                $languages[$locale] = $locale_data['/language/display'];
                //}
            }
        }

        $data['install_language'] = $install_language;
        $data['languages'] = $languages;
        $data['phase'] = 1;
        $data['phase_label'] = xarML('Step One');

        return $data;
    }
}
