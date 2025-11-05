<?php

/**
 * @package modules\roles
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Roles\UserGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Roles\UserGui;
use LocaleNotFoundException;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles user changelanguage function
 * @extends MethodClass<UserGui>
 */
class ChangelanguageMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Changes the navigation language
     * This is the external entry point to tell MLS use another language
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @see UserGui::changelanguage()
     */
    public function __invoke(array $args = [])
    {
        $this->var()->find('locale', $locale, 'str:1:', $this->mls()->getCurrentLocale());
        $this->var()->find('return_url', $return_url, 'str:1:', $this->req()->getServerVar('HTTP_REFERER'));

        $locales = $this->mls()->listSiteLocales();
        if (!isset($locales)) {
            return;
        } // throw back
        // Check if requested locale is supported
        if (!in_array($locale, $locales)) {
            throw new LocaleNotFoundException($locale);
        }
        if ($this->user()->setLocale($locale) == false) {
            // Wrong MLS mode
            // FIXME: <marco> Show a custom error here or just throw an exception?
            // <paul> throw an exception. trap it later if we want it to look nice,
            // that's the whole point of exceptions.
        }
        $this->ctl()->redirect($return_url ?: $this->mod()->getURL());
        return true;
    }
}
