<?php
/**
 * Changes the navigation language
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
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
    if (!xarVarFetch('locale',     'str:1:', $locale,     NULL, XARVAR_POST_ONLY, XARVAR_PREP_FOR_DISPLAY)) return;
    if (!xarVarFetch('return_url', 'str:1:', $return_url, NULL, XARVAR_POST_ONLY, XARVAR_PREP_FOR_DISPLAY)) return;

    $locales = xarMLSListSiteLocales();
    if (!isset($locales)) return; // throw back
    // Check if requested locale is supported
    if (!in_array($locale, $locales)) {
        $msg = xarML('Unsupported locale.');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }
    if (xarUserSetNavigationLocale($locale) == false) {
        // Wrong MLS mode
        // FIXME: <marco> Show a custom error here or just throw an exception?
        // <paul> throw an exception. trap it later if we want it to look nice,
        // that's the whole point of exceptions.
    }
    xarResponseRedirect($return_url);
}

?>