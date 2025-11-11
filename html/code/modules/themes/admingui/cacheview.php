<?php

/**
 * @package modules\themes
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Themes\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Themes\AdminGui;
use sys;

/**
 * themes admin cacheview function
 * @extends MethodClass<AdminGui>
 */
class CacheviewMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     *
     * @author Jo Dalle Nogare <jojodee@xaraya.com>
     *
     * View Cache Files
     * @param 'action' action taken on cache file
     * @param 'confirm' confirm action on delete
     * @see AdminGui::cacheview()
     */
    public function __invoke(array $args = [])
    {
        /* Get parameters from whatever input we need. */
        $this->var()->find('action', $action, 'str:1', false);
        $this->var()->find('confirm', $confirm, 'str:1:', '');
        $this->var()->find('hashn', $hashn, 'str:1:', false);
        $this->var()->find('templn', $templn, 'str:1:', false);

        /* Security check - important to do this as early as possible */
        if (!$this->sec()->checkAccess('AdminThemes')) {
            return;
        }
        $this->mod()->setVar('templcachepath', sys::varpath() . "/cache/templates");

        $cachedir  = $this->mod()->getVar('templcachepath');
        if (!file_exists($cachedir)) {
            $cachedir = sys::varpath() . "/cache/templates";
        }
        $cachefile = $this->mod()->getVar('templcachepath') . '/CACHEKEYS';
        if (!file_exists($cachefile)) {
            $cachefile = sys::varpath() . "/cache/templates/CACHEKEYS";
        }

        // CHECKME: what is this?
        $data['popup'] = false;

        /* Check for confirmation. */
        $data['authid'] = $this->sec()->genAuthKey();
        if (empty($action)) {
            /* No action set yet - display cache file list and await action */
            $data['showfiles'] = false;
            /* Generate a one-time authorisation code for this operation */
            $data['items'] = '';
            $cachelist = [];
            $cachenames = [];

            /* put all the names of the templates and hashed cache file into an array */
            umask();
            $count = 0;
            $cachekeyfile = file($cachefile);
            $fd = fopen($cachefile, 'r');
            foreach ($cachekeyfile as $line_num => $line) {
                $cachelist[] = [explode(": ", $line)];
                ++$count;
            }
            $data['count'] = $count;
            fclose($fd);

            /* generate all the URLS for cache file list */
            foreach ($cachelist as $hashname) {
                foreach ($hashname as $filen) {
                    $hashn = htmlspecialchars($filen[0]);
                    $templn = htmlspecialchars($filen[1]);
                    $fullnurl = $this->ctl()->getModuleURL(
                        'themes',
                        'admin',
                        'cacheview',
                        ['action' => 'show','templn' => $templn,'hashn' => $hashn]
                    );
                    $cachenames[$hashn] = ['hashn' => $hashn,
                        'templn' => $templn,
                        'fullnurl' => $fullnurl];
                }
            }
            asort($cachenames);
            $data['items'] = $cachenames;

            /* Return the template variables defined in this function */
            return $data;

        } elseif ($action == 'show') {
            $data['showfiles'] = true;
            $hashfile = $cachedir . '/' . $hashn . '.php';
            $newfile = [];
            $filetxt = [];
            $newfile = file($hashfile);
            $i = 0;
            foreach ($newfile as $line_num => $line) {
                ++$i;
                $filetxt[] = ['lineno' => (int) $i,
                    'linetxt' => htmlspecialchars($line)];
            }
            $data['templn'] = $templn;
            $data['hashfile'] = $hashfile;
            $data['items'] = $filetxt;
            return $data;
        }

        $this->ctl()->redirect($this->ctl()->getModuleURL('themes', 'admin', 'cacheview'));
        /*  Return */
        return true;
    }
}
