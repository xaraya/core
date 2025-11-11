<?php

/**
 * @package modules\modules
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Modules\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Modules\AdminGui;
use xarCore;
use ConfigurationException;
use Exception;

/**
 * modules admin release function
 * @extends MethodClass<AdminGui>
 */
class ReleaseMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * View recent module releases via central repository
     * @author Xaraya Development Team
     * @access public
     * @return array|void data for the template display
     * @see AdminGui::release()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!$this->sec()->checkAccess('EditModules')) {
            return;
        }

        // allow fopen
        if (!xarCore::funcIsDisabled('ini_set')) {
            ini_set('allow_url_fopen', 1);
        }
        if (!ini_get('allow_url_fopen')) {
            throw new ConfigurationException('allow_url_fopen', 'PHP is not currently configured to allow URL retrieval
            of remote files.  Please turn on #(1) to use the base module getfile userapi.');
        }
        // Check and see if a feed has been supplied to us.
        $feedfile = "https://packagist.org/search.json?type=xaraya-module&per_page=100";
        // Get the feed file (from cache or from the remote site)
        $feeddata = $this->mod()->apiFunc(
            'base',
            'user',
            'getfile',
            ['url' => $feedfile,
                'cached' => true,
                'cachedir' => 'cache/rss',
                'refresh' => 604800,
                'extension' => '.json']
        );
        if (!$feeddata) {
            return;
        }
        $info = json_decode($feeddata, true, 512, JSON_THROW_ON_ERROR);
        if (!empty($info['results'])) {
            foreach ($info['results'] as $package) {
                $feedcontent[] = ['title' => $package['name'], 'link' => $package['url'], 'description' => $package['description']];
            }
            $data['chantitle']  =   'Xaraya Modules on Packagist';
            $data['chanlink']   =   'https://packagist.org/?type=xaraya-module';
            $data['chandesc']   =   $data['chantitle'];
        } else {
            $msg = $this->ml('There is a problem with a feed.');
            throw new Exception($msg);
        }
        $data['feedcontent'] = $feedcontent;
        return $data;
    }
}
