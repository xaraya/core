<?php

/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.9.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 **/

namespace Xaraya\Modules\DynamicData;

use Xaraya\Modules\UserGuiClass;
use DataObjectUserInterface;
use SimpleObjectInterface;

/**
 * Handle the dynamicdata object GUI
 * @extends UserGuiClass<Module>
 */
class ObjectGui extends UserGuiClass
{
    /**
     * Use the DataObjectUserInterface() to handle every GUI function for objects (deprecated - see xaraya.objects in core)
     * @return mixed output display string or boolean true if redirected
     */
    public function main($args = [])
    {
        // we'll use the 'object' GUI link type here, instead of the default 'user' (+ 'admin')
        $args['linktype'] = 'object';

        $interface = new DataObjectUserInterface($args, $this->getContext(), $this->getStaticServices());

        // pass context to handler if available in function
        return $interface->handle($args, $this->getContext());
    }

    /**
     * Display the results of an object list method directly
     *
     * @author Marc Lutolf <mfl@netspan.ch>
     */
    public function runmethod(array $args = [])
    {
        // use context if available in function
        $interface = new SimpleObjectInterface($args, $this->getContext(), $this->getStaticServices());

        // set context if available in function
        return $interface->handle($args, $this->getContext());
    }
}
