<?php

/**
 * Skin Block display interface
 *
 * @package modules\themes
 * @subpackage themes
 * @copyright see the html/credits.html file in this release
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/70.html
 */

/*
 * Display block
 *
 * Skin Selection via block
 * @author Marco Canini
 * initialise block
 */
class Themes_SkinBlockDisplay extends Themes_SkinBlock implements iBlock
{
    /**
     * Display func.
     * @param $data array containing title,content
     */
    public function display(array $data = [])
    {

        if (!$this->user()->isLoggedIn()
            || (bool) $this->mod('themes')->getVar('enable_user_menu') == false) {
            return;
        }

        $data = $this->getContent();
        $data['user_themes'] = $this->mod()->apiFunc('themes', 'user', 'dropdownlist');
        if ($data['user_themes'] <= 1) {
            return;
        }
        $data['default_theme'] = $this->mod('themes')->getUserVar('default_theme');
        $data['return_url'] = ($this->ctl()->getRequestMethod() == 'GET')
            ? $this->ctl()->getCurrentURL() : $this->ctl()->getBaseURL();

        return $data;

    }
}
