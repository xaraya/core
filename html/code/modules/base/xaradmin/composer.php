<?php
/**
 * Manage third party libraries with composer
 *
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 */
/**
 * Manage third party libraries with composer
 *
 * @author Marc Lutolf
 * 
 * @param void N/A
 */
function base_admin_composer()
{
    // Security
    if(!xarSecurityCheck('ManageBase')) return;

    if (!xarVarFetch('setup',       'isset', $setup,       NULL, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('install',     'isset', $install,     NULL, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('update',      'isset', $update,      NULL, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('install_dir', 'str',   $data['install_dir'], sys::lib(), XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('package_dir', 'str',   $data['package_dir'], 'vendor', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('install_com', 'str',   $data['install_com'], 'php composer.phar update ', XARVAR_NOT_REQUIRED)) return;
    
    // Check if the installer has already been installed
    $data['installed'] = file_exists('composer') && file_exists('composer/composer.phar');

    // Default message is none
    $data['message'] = '';

    $composerdir = 'composer';
    $setup_path = $composerdir . '/composer-setup.php';
    $phar_path = $composerdir . '/composer.phar';
    
    if ($setup) {
        if (!is_dir($composerdir) && is_writable('./')) {
            $old_umask = umask(0);
            mkdir($composerdir, 0770);
            umask($old_umask);
        }
        if (!file_exists($phar_path)) {
            if (file_exists($setup_path)) {
                $output = shell_exec('rm ' . $setup_path);
            }
            $ch = curl_init();
            $fh = fopen($setup_path, 'x');
            curl_setopt_array($ch, array(
                CURLOPT_URL => 'https://getcomposer.org/installer',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_FILE => $fh
            ));
            $output = curl_exec($ch);
            curl_close($ch);
            fclose($fh);
            
            // Check the signature of the file we got
            $expected_signature = trim(file_get_contents('http://composer.github.io/installer.sig'));
            $actual_signature = trim(hash_file('sha384', $setup_path));
            if ($expected_signature == $actual_signature) {
                // Good signature: run the installer
                $output = shell_exec('php ' . $setup_path . ' --install-dir=' . $composerdir . ' --quiet');
            }
            // Remove the setup file
            $output = shell_exec('rm ' . $setup_path);
            xarController::redirect(xarServer::getCurrentURL());
        }
    } elseif ($install) {
        if (empty($data['install_com'])) {
            $data['message'] = xarML('No install command entered');
            return $data;
        }
            
        // Install the package
        $base_directory = getcwd();
        chdir($composerdir);
        $output = shell_exec($data['install_com']);
        chdir($base_directory);
        $data['message'] = 'success';
    } elseif ($update) {
        if (!xarVarFetch('composer',    'str',   $data['composer'],    '', XARVAR_NOT_REQUIRED)) return;
        xarMod::apiFunc('base', 'admin', 'write_file', array('file' => 'composer/composer.json', 'data' => $data['composer']));
    }

    $data['composer'] = trim(xarMod::apiFunc('base', 'admin', 'read_file', array('file' => 'composer/composer.json')));

    return $data;
}
?>