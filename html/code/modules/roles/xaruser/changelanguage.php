<?php
/**
 * Changes the navigation language
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */

/**
 * Changes the navigation language
 * This is the external entry point to tell MLS use another language
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 */
function roles_user_changelanguage()
{
    if (!xarVarFetch('locale',     'str:1:', $locale,     NULL, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('return_url', 'str:1:', $return_url, xarServer::getVar('HTTP_REFERER'), XARVAR_NOT_REQUIRED)) return;

    $locales = xarMLSListSiteLocales();
    if (!isset($locales)) return; // throw back
    // Check if requested locale is supported
    if (!in_array($locale, $locales)) {
        throw new LocaleNotFoundException($locale);
    }
    if (xarUserSetNavigationLocale($locale) == false) {
        // Wrong MLS mode
        // FIXME: <marco> Show a custom error here or just throw an exception?
        // <paul> throw an exception. trap it later if we want it to look nice,
        // that's the whole point of exceptions.
    }
    xarController::redirect($return_url);
}

?>
