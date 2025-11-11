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
use xarInstall;
use Exception;

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
        $this->var()->find('install_database_host', $data['database_host'], 'str::', $this->sysConfig()->getVar('DB.Host'));
        $this->var()->find('install_database_middleware', $data['database_middleware'], 'str::', $this->sysConfig()->getVar('DB.Middleware'));
        $this->var()->find('install_database_type', $data['database_type'], 'str::', $this->sysConfig()->getVar('DB.Type'));
        $this->var()->find('install_database_name', $data['database_name'], 'str::', $this->sysConfig()->getVar('DB.Name'));
        $this->var()->find('install_database_username', $data['database_username'], 'str::', $this->sysConfig()->getVar('DB.UserName'));
        $this->var()->find('install_database_password', $data['database_password'], 'str::', '');
        $this->var()->find('install_database_prefix', $data['database_prefix'], 'str::', $this->sysConfig()->getVar('DB.TablePrefix'));
        $this->var()->find('install_database_charset', $data['database_charset'], 'str::', $this->sysConfig()->getVar('DB.Charset'));

        // Supported Middleware:
        $data['database_middleware_packages']  = ['Creole' => ['name' => 'Creole', 'available' => true],
            'PDO'    => ['name' => 'PDO',    'available' => extension_loaded('pdo')],
            'DBAL'   => ['name' => 'DBAL',   'available' => false],
        ];
        // Supported Databases:
        // Not very Xaraya, but xar::mod() is not yet available
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
