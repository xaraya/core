<?php

/**
 * @package modules\themes
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Themes\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Themes\AdminApi;
use EmptyParameterException;
use xarTheme;
use sys;

sys::import('xaraya.modules.method');

/**
 * themes adminapi setstate function
 * @extends MethodClass<AdminApi>
 */
class SetstateMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Set the state of a theme
     * @author Marty Vance
     * @param array<string,mixed> $args array of optional parameters<br/>
     * integer  $args['regid'] the theme id<br/>
     * string   $args['name'] themes's name
     * integer  $args['state'] the state
     * @throws \EmptyParameterException
     * @see AdminApi::setstate()
     */
    public function __invoke(array $args = [])
    {
        // Get arguments from argument array
        extract($args);

        // Argument check
        if (isset($name)) {
            $regid = xarTheme::getRegID($name);
        }
        if (!isset($regid)) {
            throw new EmptyParameterException('regid');
        }
        if (!isset($state)) {
            throw new EmptyParameterException('state');
        }

        // Security Check
        if (!$this->sec()->checkAccess('AdminThemes')) {
            return;
        }

        // Clear cache to make sure we get newest values
        if ($this->mem()->has('Theme.Infos', $regid)) {
            $this->mem()->del('Theme.Infos', $regid);
        }

        //Get theme info
        $themeInfo = xarTheme::getInfo($regid);

        //Set up database object
        $dbconn = $this->db()->getConn();
        $xartable = $this->db()->getTables();
        $themesTable = $xartable['themes'];

        $oldState = $themeInfo['state'];

        switch ($state) {
            case xarTheme::STATE_UNINITIALISED:
                // Are we always good here?
                if ($oldState == xarTheme::STATE_MISSING_FROM_UNINITIALISED) {
                    break;
                }
                if ($oldState != xarTheme::STATE_INACTIVE) {
                    break;
                }
                break;
            case xarTheme::STATE_INACTIVE:
                if (($oldState != xarTheme::STATE_UNINITIALISED)
                    && ($oldState != xarTheme::STATE_ACTIVE)
                    && ($oldState != xarTheme::STATE_MISSING_FROM_INACTIVE)
                    && ($oldState != xarTheme::STATE_UPGRADED)) {
                    $this->session()->setVar('errormsg', $this->ml('Invalid theme state transition'));
                    return false;
                }
                break;
            case xarTheme::STATE_ACTIVE:
                if (($oldState != xarTheme::STATE_INACTIVE)
                    && ($oldState != xarTheme::STATE_MISSING_FROM_ACTIVE)) {
                    $this->session()->setVar('errormsg', $this->ml('Invalid theme state transition'));
                    return false;
                }
                break;
            case xarTheme::STATE_UPGRADED:
                if (($oldState != xarTheme::STATE_INACTIVE)
                    && ($oldState != xarTheme::STATE_ACTIVE)
                    && $oldState != xarTheme::STATE_MISSING_FROM_UPGRADED) {
                    $this->session()->setVar('errormsg', $this->ml('Invalid theme state transition'));
                    return false;
                }
                break;
        }
        // If we end up here, things are good
        $query = "UPDATE $themesTable SET state = ? WHERE regid = ? ";
        $dbconn->Execute($query, [$state,$regid]);
        return true;
    }
}
