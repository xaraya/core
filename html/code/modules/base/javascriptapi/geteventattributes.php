<?php

/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Base\JavascriptApi;

use Xaraya\Modules\Base\MethodClass;
use Xaraya\Modules\Base\JavascriptApi;
use sys;

sys::import('modules.base.method');

/**
 * base javascriptapi geteventattributes function
 * @extends MethodClass<JavascriptApi>
 */
class GeteventattributesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Handle render javascript form field tags
     * Get JavaScript event attributes for a tag.
     * Returns all submitted JavaScript fragments for a position (tag) and type (list of event names)
     * as a single string. Each attribute will be returned in name="value" format. Only non-empty
     * attributes will be returned.
     *
     * Examples:
     * Add an 'onload' trigger to the page (both examples do the same thing):
     *   <xar:javascript position="body" type="onload" code="alert('hello, world')"/>
     *   $this->mod()->apiFunc('themes','user','registerjs', array('position'=>'body', 'type'=>'onload', 'code'=>"alert('hello, world')");
     *
     * Get all the event attributes for all body tag events (this can be fetched in a page template):
     *   $this->mod()->apiFunc('base', 'javascript', 'geteventjs', array('position'=>'body', 'type'=>'onload,onunload'));
     *
     * TODO: investigate whether it is worthwhile putting all these JS functions into a
     * dedicated xarJS.php script. Going through the APIs is cumbersome, and on the whole
     * the total code involved in the JavaScript is quite small.
     * @author Jason Judge
     * @param mixed $args [position] the location of the event trigger; defaults to 'body'
     * @param mixed $args [type] the type of event trigger; several as a comma-separated list
     * @return string an empty string
     * @see JavascriptApi::geteventattributes()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var JavascriptApi $javascriptapi */
        $javascriptapi = $this->javascriptapi();

        // Initialise the event attributes string.
        $result = '';

        // Position and type are mandatory.
        // 'position' is the name or ID of the tag ('body', 'mytag', etc.).
        if (empty($position)) {
            // The body tag is the most likely place the events will be used.
            $position = 'body';
        }

        // 'type' is the event types ('onload', 'onmouseup', etc.), supplied
        // as a comma-separated list.
        if (empty($type)) {
            return $result;
        } else {
            $types = explode(',', strtolower($type));
        }

        foreach ($types as $type) {
            $js = $javascriptapi->geteventjs(
                ['position' => $position, 'type' => $type]
            );
            if (!empty($js)) {
                // Format the attribute.
                $result .= ' ' . $type . '="' . htmlspecialchars($js) . '"';
            }
        }

        // Return the result, in the form of a string containing attributes and values.
        return $result;
    }
}
