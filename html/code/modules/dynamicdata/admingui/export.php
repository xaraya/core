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
use Xaraya\Modules\DynamicData\UtilApi;
use DataObjectFactory;
use DataPropertyMaster;
use RuntimeException;
use xarController;
use xarDB;
use xarLocale;
use xarMod;
use xarSecurity;
use xarTpl;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * dynamicdata admin export function
 * @extends MethodClass<AdminGui>
 */
class ExportMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Export an object definition or an object item to XML
     * @see AdminGui::export()
     */
    public function __invoke(array $args = [])
    {
        /** @var UtilApi $utilapi */
        $utilapi = $this->utilapi();
        // Security
        if (!$this->sec()->checkAccess('AdminDynamicData')) {
            return;
        }

        extract($args);

        if (!$this->var()->check('objectid', $objectid, 'isset', 1)) {
            return;
        }
        if (!$this->var()->check('name', $name)) {
            return;
        }
        if (!$this->var()->check('module_id', $moduleid)) {
            return;
        }
        if (!$this->var()->check('itemtype', $itemtype)) {
            return;
        }
        if (!$this->var()->check('itemid', $itemid)) {
            return;
        }
        if (!$this->var()->check('tofile', $tofile)) {
            return;
        }
        if (!$this->var()->check('convert', $convert)) {
            return;
        }
        if (!$this->var()->check('format', $format, 'isset', 'xml')) {
            return;
        }

        $data = [];
        $data['menutitle'] = $this->ml('Dynamic Data Utilities');

        // set context if available in function
        $myobject = $this->data()->getObject(
            ['objectid' => $objectid,
                'name'     => $name,
                'itemid'   => $itemid,
                'allprops' => true]
        );

        if (!isset($myobject) || empty($myobject->label)) {
            $data['label'] = $this->ml('Unknown Object');
            $data['xml'] = '';
            return $data;
        }
        // check security of the object
        if (!$myobject->checkAccess('config')) {
            $msg = $this->ml('Configure #(1) is forbidden', $myobject->label);
            return $this->ctl()->forbidden($msg);
        }

        $proptypes = $this->prop()->getPropertyTypes();

        $prefix = $this->db()->getPrefix();
        $prefix .= '_';

        $xml = '';
        $ext = '';

        // export object definition
        if (empty($itemid)) {
            $data['label'] = $this->ml('Export Object Definition for #(1)', $myobject->label);

            $xml = $utilapi->export([
                'objectref' => &$myobject,
                'format' => $format,
                'tofile' => $tofile,
            ]);
            if ($format != 'php') {
                $ext = '-def';
            }

            /**
            if (!empty($myobject->datastores) && count($myobject->datastores) == 1 && !empty($myobject->datastores['_dynamic_data_'])) {
                $data['convertlink'] = $this->mod()->getURL('admin','export',
                                                 array('objectid' => $myobject->objectid,
                                                       'convert'  => 1));
                if (!empty($convert)) {
                    if (!$utilapi->maketable(array('objectref' => &$myobject))) return;

                }
            }
             */

            // export specific item
        } elseif (is_numeric($itemid)) {
            $data['label'] = $this->ml('Export Data for #(1) # #(2)', $myobject->label, $itemid);

            $xml = $utilapi->exportItem(['objectid' => $myobject->objectid,
                    'itemid' => $itemid,
                    'format' => $format]
            );
            $ext = '-dat.' . $itemid;

            // export all items (better save this to file, e.g. in var/cache/...)
        } elseif ($itemid == 'all') {
            $data['label'] = $this->ml('Export Data for all #(1) Items', $myobject->label);

            $xml = $utilapi->exportItems(['objectid' => $myobject->objectid,
                    'format' => $format]
            );
            $ext = '-dat';

        } else {
            $data['label'] = $this->ml('Unknown Request for #(1)', $myobject->label);
            $xml = '';
        }

        $data['formlink'] = $this->mod()->getURL(
            'admin',
            'export',
            ['objectid' => $myobject->objectid,
                'itemid'   => 'all']
        );
        $data['filelink'] = $this->mod()->getURL(
            'admin',
            'export',
            ['objectid' => $myobject->objectid,
                'itemid'   => 'all',
                'tofile'   => 1]
        );
        $data['savelink'] = $this->mod()->getURL(
            'admin',
            'export',
            ['objectid' => $myobject->objectid,
                'tofile'   => 1]
        );
        $data['generatelink'] = $this->mod()->getURL(
            'admin',
            'export',
            ['objectid' => $myobject->objectid,
                'format' => 'php',
                'tofile'   => 1]
        );

        if (!empty($tofile) && !empty($ext)) {
            $varDir = sys::varpath();
            $outfile = $varDir . '/uploads/' . $this->var()->prepPath($myobject->name) . $ext . '.' . xarLocale::formatDate('%Y%m%d%H%M%S', time()) . '.' . $format;
            $fp = @fopen($outfile, 'w');
            if (!$fp) {
                $data['xml'] = $this->ml('Unable to open file #(1)', $outfile);
                return $data;
            }
            $written = fwrite($fp, $xml);
            fclose($fp);
            $towrite = strlen($xml);
            if ($written < $towrite) {
                throw new RuntimeException("could only write {$written}/{$towrite} bytes!");
            }
            $xml = $this->ml('Data saved to #(1)', $outfile);
        }

        $data['objectid'] = $objectid;
        $data['xml'] = $this->var()->prep($xml);
        $data['format'] = $format;

        $this->tpl()->setPageTemplateName('admin');

        return $data;
    }
}
