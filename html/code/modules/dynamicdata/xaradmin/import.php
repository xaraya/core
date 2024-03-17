<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Import an object definition or an object item from XML
 */
function dynamicdata_admin_import(array $args = [], $context = null)
{
    // Security
    if(!xarSecurity::check('AdminDynamicData')) {
        return;
    }

    $data = ['prefix' => null];
    if(!xarVar::fetch('basedir', 'isset', $basedir, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('import', 'isset', $import, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('xml', 'isset', $xml, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('refresh', 'isset', $refresh, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('keepitemid', 'isset', $keepitemid, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('overwrite', 'checkbox', $overwrite, false, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('prefix', 'isset', $data['prefix'], xarDB::getPrefix(), xarVar::DONT_SET)) {
        return;
    }

    extract($args);

    $data['warning'] = '';
    $data['options'] = [];

    if (empty($basedir)) {
        $basedir = sys::code() . 'modules/dynamicdata';
    }
    $data['basedir'] = $basedir;
    $data['authid'] = xarSec::genAuthKey();

    $filetype = 'xml';
    $files = xarMod::apiFunc(
        'dynamicdata',
        'admin',
        'browse',
        ['basedir' => $basedir,
        'filetype' => $filetype]
    );
    if (!isset($files) || count($files) < 1) {
        $data['warning'] = xarML('There are currently no XML files available for import in "#(1)"', $basedir);
        return $data;
    }

    if (empty($refresh) && (!empty($import) || !empty($xml))) {
        if (!xarSec::confirmAuthKey()) {
            return xarTpl::module('privileges', 'user', 'errors', ['layout' => 'bad_author']);
        }

        if (empty($keepitemid)) {
            $keepitemid = 0;
        }
        if (!empty($import)) {
            $found = '';
            $file = '';
            foreach ($files as $file) {
                if ($file == $import) {
                    $found = $file;
                    break;
                }
            }
            if (empty($found) || !file_exists($basedir . '/' . $file)) {
                throw new FileNotFoundException($basedir, 'No files were found to import in directory "#(1)"');
            }
            try {
                $objectid = xarMod::apiFunc(
                    'dynamicdata',
                    'util',
                    'import',
                    ['file' => $basedir . '/' . $file,
                    'keepitemid' => $keepitemid,
                    'overwrite' =>  $overwrite,
                    'prefix' => $data['prefix']]
                );
            } catch (DuplicateException $e) {
                return xarTpl::module('dynamicdata', 'user', 'errors', ['layout' => 'duplicate_name', 'name' => $e->getMessage()]);
            } catch (Exception $e) {
                return xarTpl::module('dynamicdata', 'user', 'errors', ['layout' => 'bad_definition', 'name' => $e->getMessage()]);
            }
        } else {
            try {
                $objectid = xarMod::apiFunc(
                    'dynamicdata',
                    'util',
                    'import',
                    ['xml' => $xml,
                    'keepitemid' => $keepitemid,
                    'overwrite' =>  $overwrite,
                    'prefix' => $data['prefix']]
                );
            } catch (DuplicateException $e) {
                return xarTpl::module('dynamicdata', 'user', 'errors', ['layout' => 'duplicate_name', 'name' => $e->getMessage()]);
            } catch (Exception $e) {
                return xarTpl::module('dynamicdata', 'user', 'errors', ['layout' => 'bad_definition', 'name' => $e->getMessage()]);
            }
        }
        if (empty($objectid)) {
            return;
        }

        xarController::redirect(xarController::URL(
            'dynamicdata',
            'admin',
            'modifyprop',
            ['itemid' => $objectid]
        ));
        return true;
    }

    natsort($files);
    array_unshift($files, '');
    foreach ($files as $file) {
        $data['options'][] = [
            'id' => $file,
            'name' => $file,
        ];
    }

    xarTpl::setPageTemplateName('admin');

    return $data;
}
