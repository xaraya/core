<?php

/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Base\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Base\AdminGui;
use Xaraya\Modules\Base\AdminApi;
use xarController;
use xarMod;
use xarSecurity;
use xarServer;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * base admin composer function
 * @extends MethodClass<AdminGui>
 */
class ComposerMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Manage third party libraries with composer
     * @author Marc Lutolf
     * @see AdminGui::composer()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security
        if (!$this->sec()->checkAccess('ManageBase')) {
            return;
        }

        $data = [];
        $this->var()->find('setup', $setup);
        $this->var()->find('install', $install);
        $this->var()->find('update', $update);
        $this->var()->find('install_dir', $data['install_dir'], 'str', sys::lib());
        $this->var()->find('package_dir', $data['package_dir'], 'str', 'vendor');
        $this->var()->find('install_com', $data['install_com'], 'str', 'php composer.phar update ');

        // Check that the libcurl extension is installed
        $data['libcurl']             = extension_loaded('curl');

        // Check if the installer has already been installed
        $data['installed'] = file_exists('composer') && file_exists('composer/composer.phar');
        $data['composer_file'] = 'composer/composer.json';
        if (empty($data['installed'])) {
            $root = sys::root();
            // flat install supporting symlinks
            if (empty($root)) {
                $root = realpath(dirname(realpath(xarServer::getVar('SCRIPT_FILENAME'))) . '/../');
                //$vendor = realpath(dirname(realpath(xarServer::getVar('SCRIPT_FILENAME'))) . '/../vendor');
            } else {
                $root = realpath($root);
                //$vendor = realpath($root . 'vendor');
            }
            //require_once $vendor .'/autoload.php';
            $data['installed'] = $root && is_dir($root) && file_exists($root . '/composer.json');
            if (!empty($data['installed'])) {
                $data['composer_file'] = $root . '/composer.json';
            }
        }
        $data['writable'] = false;
        if (!empty($data['composer_file'])) {
            $data['writable'] = file_exists($data['composer_file']) && is_writable($data['composer_file']);
        }

        // Default message is none
        $data['message'] = [];

        $composerdir = 'composer';
        $setup_path  = $composerdir . '/composer-setup.php';
        $phar_path   = $composerdir . '/composer.phar';

        if ($setup) {
            if (!is_dir($composerdir) && is_writable('./')) {
                $old_umask = umask(0);
                mkdir($composerdir, 0o770);
                umask($old_umask);
            }
            if (!file_exists($phar_path)) {
                if (file_exists($setup_path)) {
                    $output = shell_exec('rm ' . $setup_path);
                }
                $ch = curl_init();
                $fh = fopen($setup_path, 'x');
                curl_setopt_array($ch, [
                    CURLOPT_URL => 'https://getcomposer.org/installer',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_FILE => $fh,
                ]);
                $output = curl_exec($ch);
                curl_close($ch);
                fclose($fh);

                // Check the signature of the file we got
                $expected_signature = trim(file_get_contents('http://composer.github.io/installer.sig'));
                $actual_signature = trim(hash_file('sha384', $setup_path));
                if ($expected_signature == $actual_signature) {
                    // Good signature: run the installer
                    $output = shell_exec('php ' . $setup_path . ' --install-dir=' . $composerdir . ' --quiet');
                    if (!empty($output)) {
                        $data['message'][] = $output;
                    }
                }
                // Remove the setup file
                $output = shell_exec('rm ' . $setup_path);
                if (!empty($output)) {
                    $data['message'][] = $output;
                }
                if (empty($data['message'])) {
                    $this->ctl()->redirect($this->ctl()->getCurrentURL());
                }
            }
        } elseif ($install) {
            if (empty($data['install_com'])) {
                $data['message'][] = $this->ml('No install command entered');
                return $data;
            }

            // Install the package
            $base_directory = getcwd();
            chdir($composerdir);
            $output = shell_exec($data['install_com']);
            chdir($base_directory);
            $data['message'][] = 'success';
        } elseif ($update) {
            $this->var()->find('composer', $data['composer'], 'str', '');
            $adminapi->write_file(['file' => $data['composer_file'], 'data' => $data['composer']]);
        }

        $data['composer'] = trim($adminapi->read_file(['file' => $data['composer_file']]));

        return $data;
    }
}
