<?php
/**
 * Installer
 *
 * @package modules\installer\installer
 * @subpackage installer
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/200.html
 */

/* Do not allow this script to run if the install script has been removed.
 * This assumes the install.php and index.php are in the same directory.
 * @author Paul Rosania
 * @author Marcel van der Boom <marcel@hsdev.com>
 */

/**
 * Phase 3: Check system settings
 *
 * @access private
 * @param agree string
 * @return array data for the template display
 */
function installer_admin_phase3()
{
    if (!file_exists('install.php')) { throw new Exception('Already installed');}
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);
    if (!xarVarFetch('agree','regexp:(agree|disagree)',$agree)) return;

    $retry=1;

    if ($agree != 'agree') {
        // didn't agree to license, don't install
        header("Location: install.php?install_phase=2&install_language='.$install_language.'&retry=1");
    }

    //Defaults
    $systemConfigIsWritable   = false;
    $systemConfigDistIsReadable   = false;
    $cacheTemplatesIsWritable = false;
    $rssTemplatesIsWritable   = false;
    $metRequiredPHPVersion    = false;

    $systemVarDir             = sys::varpath();
    $cacheDir                 = $systemVarDir . xarConst::CACHEDIR;
    $cacheTemplatesDir        = $systemVarDir . xarConst::TPL_CACHEDIR;
    $rssTemplatesDir          = $systemVarDir . xarConst::RSS_CACHEDIR;
    $systemConfigFile         = $systemVarDir . '/' . sys::CONFIG;
    $systemConfigDistFile     = $systemVarDir . '/' . sys::CONFIG . '.dist';
    $phpLanguageDir           = $systemVarDir . '/locales/' . $install_language . '/php';
    $xmlLanguageDir           = $systemVarDir . '/locales/' . $install_language . '/xml';

    if (function_exists('version_compare')) {
        if (version_compare(PHP_VERSION,PHP_REQUIRED_VERSION,'>=')) $metRequiredPHPVersion = true;
    }

    // If there is no system.config file, attempt to create it
    $systemConfigDistIsReadable = is_readable($systemConfigDistFile);
    if ($systemConfigDistIsReadable && !file_exists($systemConfigFile)) {
        try {
            copy($systemConfigDistFile, $systemConfigFile);
        } catch (Exception $e) {}
    }
    
    $systemConfigIsWritable     = is_writable($systemConfigFile);
    $cacheIsWritable            = check_dir($cacheDir);
    $cacheTemplatesIsWritable   = (check_dir($cacheTemplatesDir) || @mkdir($cacheTemplatesDir, 0700));
    $rssTemplatesIsWritable     = (check_dir($rssTemplatesDir) || @mkdir($rssTemplatesDir, 0700));
    $phpLanguageFilesIsWritable = xarMLS::iswritable($phpLanguageDir);
    $xmlLanguageFilesIsWritable = xarMLS::iswritable($xmlLanguageDir);
    $maxexectime = trim(ini_get('max_execution_time'));
    $memLimit = trim(ini_get('memory_limit'));
    $memLimit = empty($memLimit) ? xarML('Undetermined') : $memLimit;
    $memVal = substr($memLimit,0,strlen($memLimit)-1);
    switch(strtolower($memLimit{strlen($memLimit)-1})) {
        case 'g': $memVal *= 1024;
        case 'm': $memVal *= 1024;
        case 'k': $memVal *= 1024;
    }

    // Extension Check
    $data['xmlextension']             = extension_loaded('xml');
    $data['xslextension']             = extension_loaded('xsl');
    $data['mysqlextension']           = extension_loaded('mysql');
    $data['mysqliextension']          = extension_loaded('mysqli');
    $data['pgsqlextension']           = extension_loaded('pgsql');
    $data['sqliteextension']          = extension_loaded('sqlite');
    $data['pdosqliteextension']       = extension_loaded('pdo_sqlite');

    $data['metRequiredPHPVersion']      = $metRequiredPHPVersion;
    $data['phpVersion']                 = PHP_VERSION;
    $data['cacheDir']                   = $cacheDir;
    $data['cacheIsWritable']            = $cacheIsWritable;
    $data['cacheTemplatesDir']          = $cacheTemplatesDir;
    $data['cacheTemplatesIsWritable']   = $cacheTemplatesIsWritable;
    $data['rssTemplatesDir']            = $rssTemplatesDir;
    $data['rssTemplatesIsWritable']     = $rssTemplatesIsWritable;
    $data['systemConfigFile']           = $systemConfigFile;
    $data['systemConfigIsWritable']     = $systemConfigIsWritable;
    $data['systemConfigDistFile']       = $systemConfigDistFile;
    $data['systemConfigDistIsReadable'] = $systemConfigDistIsReadable;
    $data['phpLanguageDir']             = $phpLanguageDir;
    $data['phpLanguageFilesIsWritable'] = $phpLanguageFilesIsWritable;
    $data['xmlLanguageDir']             = $xmlLanguageDir;
    $data['xmlLanguageFilesIsWritable'] = $xmlLanguageFilesIsWritable;
    $data['maxexectime']                = $maxexectime;
    $data['maxexectimepass']            = $maxexectime<=30;
    $data['memory_limit']               = $memLimit;
    $data['memory_warning']             = $memLimit == xarML('Undetermined');
    $data['metMinMemRequirement']       = $memVal >= 8 * 1024 * 1024 || $data['memory_warning'];

    $data['language']    = $install_language;
    $data['phase']       = 3;
    $data['phase_label'] = xarML('Step Three');

    // We only check this extension if MySQL is loaded
    if ($data['mysqlextension']) {
        $data['mysql_required_version']     = MYSQL_REQUIRED_VERSION;
        ob_start();
        phpinfo(INFO_MODULES);
        $info = ob_get_contents();
        ob_end_clean();
        $info = stristr($info, 'Client API version');
        preg_match('/[1-9].[0-9].[1-9][0-9]/', $info, $match);
        $data['mysql_version_ok'] = version_compare($match[0],MYSQL_REQUIRED_VERSION,'ge');
        $data['mysql_version']          = $match[0];
    }

    return $data;
}

/**
 * Check whether directory permissions allow to write and read files inside it
 *
 * @access private
 * @param string dirname directory name
 * @return boolean true if directory is writable, readable and executable
 */
function check_dir($dirname)
{
    if (!file_exists('install.php')) { throw new Exception('Already installed');}
    if (@touch($dirname . '/.check_dir')) {
        $fd = @fopen($dirname . '/.check_dir', 'r');
        if ($fd) {
            fclose($fd);
            unlink($dirname . '/.check_dir');
        } else {
            return false;
        }
    } else {
        return false;
    }
    return true;
}

?>