<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\DynamicData\AdminGui;

use Xaraya\Modules\DynamicData\MethodClass;
use Xaraya\Modules\DynamicData\AdminGui;
use Xaraya\Modules\DynamicData\AdminApi;
use Xaraya\Modules\DynamicData\UtilApi;
use DuplicateException;
use Exception;
use FileNotFoundException;
use xarController;
use xarDB;
use xarMod;
use xarSec;
use xarSecurity;
use xarTpl;
use xarVar;
use sys;

sys::import('modules.dynamicdata.method');


/**
 * dynamicdata admin import function
 * @extends MethodClass<AdminGui>
 */
class ImportMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Import an object definition or an object item from XML
     * @see AdminGui::import()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        /** @var UtilApi $utilapi */
        $utilapi = $this->utilapi();
        // Security
        if (!$this->sec()->checkAccess('AdminDynamicData')) {
            return;
        }

        $data = ['prefix' => null];
        $this->var()->check('basedir', $basedir);
        $this->var()->check('import', $import);
        $this->var()->check('xml', $xml);
        $this->var()->check('refresh', $refresh);
        $this->var()->check('keepitemid', $keepitemid);
        $this->var()->check('overwrite', $overwrite, 'checkbox', false);
        $this->var()->check('prefix', $data['prefix'], 'isset', $this->db()->getPrefix());

        extract($args);

        $data['warning'] = '';
        $data['options'] = [];

        if (empty($basedir)) {
            $basedir = sys::code() . 'modules/dynamicdata';
        }
        $data['basedir'] = $basedir;
        $data['authid'] = $this->sec()->genAuthKey();

        $filetype = 'xml';
        $files = $adminapi->browse(['basedir' => $basedir,
                'filetype' => $filetype]
        );
        if (!isset($files) || count($files) < 1) {
            $data['warning'] = $this->ml('There are currently no XML files available for import in "#(1)"', $basedir);
            return $data;
        }

        if (empty($refresh) && (!empty($import) || !empty($xml))) {
            if (!$this->sec()->confirmAuthKey()) {
                return $this->ctl()->badRequest('bad_author');
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
                    $objectid = $utilapi->import(['file' => $basedir . '/' . $file,
                            'keepitemid' => $keepitemid,
                            'overwrite' =>  $overwrite,
                            'prefix' => $data['prefix']]
                    );
                } catch (DuplicateException $e) {
                    return $this->tpl()->module('dynamicdata', 'user', 'errors', ['layout' => 'duplicate_name', 'name' => $e->getMessage()]);
                } catch (Exception $e) {
                    return $this->tpl()->module('dynamicdata', 'user', 'errors', ['layout' => 'bad_definition', 'name' => $e->getMessage()]);
                }
            } else {
                try {
                    $objectid = $utilapi->import(['xml' => $xml,
                            'keepitemid' => $keepitemid,
                            'overwrite' =>  $overwrite,
                            'prefix' => $data['prefix']]
                    );
                } catch (DuplicateException $e) {
                    return $this->tpl()->module('dynamicdata', 'user', 'errors', ['layout' => 'duplicate_name', 'name' => $e->getMessage()]);
                } catch (Exception $e) {
                    return $this->tpl()->module('dynamicdata', 'user', 'errors', ['layout' => 'bad_definition', 'name' => $e->getMessage()]);
                }
            }
            if (empty($objectid)) {
                return;
            }

            $this->ctl()->redirect($this->mod()->getURL(
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

        $this->tpl()->setPageTemplateName('admin');

        return $data;
    }
}
