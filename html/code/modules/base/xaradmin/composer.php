<?php
/**
 *  View recent extension releases
 *
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 */
/**
 * View recent module releases via central repository
 *
 * @author Marc Lutolf
 * 
 * @param void N/A
 */
function base_admin_composer()
{
    // Security
    if(!xarSecurityCheck('ManageBase')) return;

    if (!xarVarFetch('setup', 'isset', $setup, NULL, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('install', 'isset', $install, NULL, XARVAR_NOT_REQUIRED)) return;
    
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

            $output = shell_exec('php ' . $setup_path . ' --install-dir=' . $composerdir . ' --quiet');
            $output = shell_exec('rm ' . $setup_path);
        }
    } 
    
    // Check if the installer has already been installed
    $data['installed'] = file_exists('composer') && file_exists('composer/composer.phar');

    return $data;
}
?>
