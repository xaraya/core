<?php

/**
 * Dynamic Object User Interface Handler
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

namespace Xaraya\DataObject\Handlers;

use Xaraya\Services\WithServicesInterface;
use Xaraya\Services\WithServicesTrait;

/**
 * Stats Reports for Dynamic Object User Interface Handler
 */
class StatsReports implements WithServicesInterface
{
    use WithServicesTrait;

    public string $modName = 'dynamicdata';
    public string $objectName = 'sample';

    public function __construct(string $modName, string $objectName, $xar = null)
    {
        // depending on current module
        $this->modName = $modName;
        $this->objectName = $objectName;
        $this->setServicesClass($xar);
    }

    /**
     * Summary of getReportList
     * @return array<mixed>
     */
    public function getReportList()
    {
        $xar = $this->getServicesClass();
        $serialreports = $xar->mod($this->modName)->getVar('reportlist.' . $this->objectName);
        if (!empty($serialreports)) {
            $reportlist = unserialize($serialreports);
        } else {
            $reportlist = [];
        }
        return $reportlist;
    }

    /**
     * Summary of getReport
     * @param string $report
     * @return array<mixed>
     */
    public function getReport($report)
    {
        $key = 'report.' . $this->objectName . '.' . $report;
        if (strlen($key) > 64) {
            $key = 'report.' . md5($key);
        }
        $xar = $this->getServicesClass();
        $serialinfo = $xar->mod($this->modName)->getVar($key);
        if (!empty($serialinfo)) {
            $info = unserialize($serialinfo);
        } else {
            $info = [];
        }
        return $info;
    }

    /**
     * Summary of saveReport
     * @param string $report
     * @param array<mixed> $stats
     * @param array<mixed> $info
     * @return void
     */
    public function saveReport($report, $stats, $info)
    {
        $xar = $this->getServicesClass();
        $reportlist = $this->getReportList();
        if (empty($reportlist) || !in_array($report, $reportlist)) {
            // only keep the last 20 reports per object
            if (count($reportlist) > 20) {
                $oldreport = array_pop($reportlist);
                $this->deleteReport($oldreport);
            }
            // add the new report at the front of the list
            array_unshift($reportlist, $report);
            $xar->mod($this->modName)->setVar('reportlist.' . $this->objectName, serialize($reportlist));
        }
        // add stats to info so we can edit it afterwards
        $info['stats'] = $stats;
        $key = 'report.' . $this->objectName . '.' . $report;
        if (strlen($key) > 64) {
            $key = 'report.' . md5($key);
        }
        $xar->mod($this->modName)->setVar($key, serialize($info));
    }

    /**
     * Summary of deleteReport
     * @param string $report
     * @return void
     */
    public function deleteReport($report)
    {
        $key = 'report.' . $this->objectName . '.' . $report;
        if (strlen($key) > 64) {
            $key = 'report.' . md5($key);
        }
        $xar = $this->getServicesClass();
        $xar->mod($this->modName)->setVar($key, null);
    }
}
