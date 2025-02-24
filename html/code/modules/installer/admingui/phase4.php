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
use xarController;
use xarInstall;
use xarServer;
use xarSystemVars;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * installer admin phase4 function
 * @extends MethodClass<AdminGui>
 */
class Phase4Method extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Phase 4: Database Settings Page
     * @access private
     * @return array|bool data for the template display
     * @see AdminGui::phase4()
     */
    public function __invoke(array $args = [])
    {
        if (!file_exists('install.php')) {
            throw new Exception('Already installed');
        }
        $this->var()->find('install_language', $install_language, 'str::', 'en_US.utf-8');
        $this->var()->find('continue', $continue, 'isset', null);

        $data = [];
        $this->var()->find('install_database_host', $data['database_host'], 'str::', xarSystemVars::get(sys::CONFIG, 'DB.Host'));
        $this->var()->find('install_database_middleware', $data['database_middleware'], 'str::', xarSystemVars::get(sys::CONFIG, 'DB.Middleware'));
        $this->var()->find('install_database_type', $data['database_type'], 'str::', xarSystemVars::get(sys::CONFIG, 'DB.Type'));
        $this->var()->find('install_database_name', $data['database_name'], 'str::', xarSystemVars::get(sys::CONFIG, 'DB.Name'));
        $this->var()->find('install_database_username', $data['database_username'], 'str::', xarSystemVars::get(sys::CONFIG, 'DB.UserName'));
        $this->var()->find('install_database_password', $data['database_password'], 'str::', '');
        $this->var()->find('install_database_prefix', $data['database_prefix'], 'str::', xarSystemVars::get(sys::CONFIG, 'DB.TablePrefix'));
        $this->var()->find('install_database_charset', $data['database_charset'], 'str::', xarSystemVars::get(sys::CONFIG, 'DB.Charset'));

        // Supported Middleware:
        $data['database_middleware_packages']  = ['Creole' => ['name' => 'Creole', 'available' => true],
            'PDO'    => ['name' => 'PDO',    'available' => extension_loaded('pdo')],
            'DBAL'   => ['name' => 'DBAL',   'available' => false],
        ];
        // Supported Databases:
        // Not very Xaraya, but xarMod is not yet available
        //sys::import('modules.base.adminapi.get_supported_dbs');
        sys::import('modules.base.adminapi');
        $data['database_types'] = \Xaraya\Modules\Base\AdminApi::getSupportedDbs($data['database_middleware']);

        // The Continue button was clicked
        if (isset($continue)) {
            // Save everything to the configuration file
            $variables['DB.Middleware'] =  $data['database_middleware'];
            $variables['DB.Type'] =        $data['database_type'];
            $variables['DB.Host'] =        $data['database_host'];
            $variables['DB.UserName'] =    $data['database_username'];
            $variables['DB.Password'] =    $data['database_password'];
            $variables['DB.Name'] =        $data['database_name'];
            $variables['DB.TablePrefix'] = $data['database_prefix'];
            $variables['DB.Charset'] =     $data['database_charset'];
            xarInstall::apifunc('modifysystemvars', ['variables' => $variables]);

            // Jump to the next page
            $this->ctl()->redirect($this->ctl()->getCurrentURL(['install_phase' => 5]));
            return true;
        }

        $data['language'] = $install_language;
        $data['phase'] = 4;
        $data['phase_label'] = $this->ml('Step Four');

        return $data;
    }
}
