<?php
/**
 * Exception handlers class
 *
 * @package exceptions
 * @copyright (C) 2006 The Digital Development Foundation
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @author Marcel van der Boom <marcel@hsdev.com>
 */

final class ExceptionHandlers
{
    /**
     * Default Exception handler for unhandled exceptions
     *
     * This handler is called when an exception is raised and otherwise unhandled
     * Execution stops directly after this handler runs. (or any exception handler for that matter)
     * The base exception object is documented here: http://www.php.net/manual/en/language.exceptions.php
     * but we dont want to instantiate that directly, but rather one of our derived classes.
     * We define this handler here, because it needs to be defined before set_exception_handler
     *
     * @param  Exception $exception The exception object
     * @todo Make exception handling the default error handling and get rid of the redundant parts
     * @return void
     */
    public static function defaulthandler(Exception $e)
    {
        // This handles exceptions, which can arrive directly or through xarErrorSet.
        // if through xarErrorSet there will be something waiting for us on the stack
        if(xarCurrentErrorType() != XAR_NO_EXCEPTION) {
            // TODO: phase this out
            $msg = xarErrorRender('template');
        } else {
            // Poor mans final fallback for unhandled exceptions (simulate the same rendering as first part of the if
            $data = array('major' => 'MAJOR TBD (Code was: '. $e->getCode().')',
                          'type'  => get_class($e), 'title' => get_class($e) . ' ['.$e->getCode().'] was raised (native)',
                          'short' => $e->getMessage(), 'long' => 'LONG msg TBD',
                          'hint'  => 'HINT TBD', 'stack' => '<pre>'. $e->getTraceAsString()."</pre>",
                          'product' => 'Product TBD', 'component' => 'Component TBD');
            // If we have em, use em
            if(function_exists('xarTplGetThemeDir') && function_exist('xarTplFile')) {
                $theme_dir = xarTplGetThemeDir(); $template="systemerror";
                if(file_exists($theme_dir . '/modules/base/message-' . $template . '.xt')) {
                    $msg = xarTplFile($theme_dir . '/modules/base/message-' . $template . '.xt', $data);
                } else {
                    $msg = xarTplFile('modules/base/xartemplates/message-' . $template . '.xd', $data);
                }
            } else {
                // no templating yet, pass direct and render as rawhtml
                ExceptionHandlers::RenderRaw($e);
                die();
        }
        }
        xarErrorFree();
        // Make an attempt to render the page, hoping we have everything in place still
        try {
            echo xarTpl_renderPage($msg);
        } catch( Exception $e) {
            // Oh well, pick up the bones
            ExceptionHandlers::RenderRaw($e);
        }
    }

    // Lowest level handler, should always work, no assumptions whatsoever
    public static function bone(Exception $e)
    {
        ExceptionHandlers::RenderRaw($e);
    }

    private static function RenderRaw(Exception $e)
    {
        // TODO: how many assumptions can we make about the rendering capabilities of the client here?
        $out="<pre>";
        $out.= 'Error: '.$e->getCode().": ".get_class($e)."\n";
        $out.= $e->getMessage()."\n\n";
        $out.= $e->getTraceAsString();
        $out.= "</pre>";
        echo $out;
    }
}
?>