<?php
/**
 * Exception handlers class
 *
 * @package exceptions
 * @copyright (C) 2006 The Digital Development Foundation
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @author Marcel van der Boom <marcel@hsdev.com>
 **/

interface IExceptionHandlers
{
    public static function defaulthandler(Exception $e);
    public static function phperrors($errorType, $errorString, $file, $line);
}

class ExceptionHandlers implements IExceptionHandlers
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
     * @todo Get rid of the redundant parts
     * @return void
     */
    public static function defaulthandler(Exception $e)
    {
        // Make an attempt to render the page, hoping we have everything in place still
        // CHECKME: Hmm, is this a problem, as we're already in a handler?
        //          If it is not, then we really only have to consider really fatal errors 
        //          (which wont get caught by any handler) and make sure the rest ends up
        //          in the bone handler
        try {
            // Try to get the full path location out of the trace
            $root  = str_replace('includes/exceptions','',dirname(__FILE__));
            $trace = str_replace($root,'/',$e->getTraceAsString());
            $data = array('major'     => 'MAJOR TBD (Code was: '. $e->getCode().')',
                          'type'      => get_class($e), // consider stripping of 'Exception'
                          'title'     => get_class($e) . ' ['.$e->getCode().'] was raised (native)',
                          'short'     => htmlspecialchars($e->getMessage()), 
                          'long'      => 'LONG msg TBD',
                          'hint'      => (method_exists($e,'getHint'))? htmlspecialchars($e->getHint()) : 'No hint available',
                          'stack'     => htmlspecialchars($trace),
                          'product'   => 'Product TBD', 
                          'component' => 'Component TBD');
            // If we have em, use em
            if(function_exists('xarTplGetThemeDir') && function_exists('xarTplFile')) {
                $theme_dir = xarTplGetThemeDir(); $template="systemerror";
                if(file_exists($theme_dir . '/modules/base/message-' . $template . '.xt')) {
                    $msg = xarTplFile($theme_dir . '/modules/base/message-' . $template . '.xt', $data);
                } else {
                    $msg = xarTplFile('modules/base/xartemplates/message-' . $template . '.xd', $data);
                }            
                echo xarTpl_renderPage($msg);
            } else {
                // Rethrow it, we cant handle it.
                throw $e;
            }
        } catch( Exception $e) {
            // Oh well, pick up the bones
            ExceptionHandlers::bone($e);
        }
    }

    // Lowest level handler, should always work, no assumptions whatsoever
    public static function bone(Exception $e)
    {
        echo ExceptionHandlers::RenderRaw($e);
    }

    /**
     * PHP error handler bridge to Xaraya exceptions
     *
     * @author Marco Canini <marco@xaraya.com>
     * @access private
     * @return void
     */
    final public static function phperrors($errorType, $errorString, $file, $line)
    {
        //Checks for a @ presence in the given line, should stop from setting Xaraya errors
        try {
            // We'll try
            $errLevel = xarCore_getSystemVar('Exception.ErrorLevel');
        } catch(Exception $e) {
            // Oh well.
            $errLevel = E_STRICT;
        }
        if (!error_reporting() || !($errorType & $errLevel)) {
            // Log the message so it is not lost.
            // TODO: make this message available to calling functions that suppress errors through '@'.
            $msg = "PHP error code $errorType at line $line of $file: $errorString";
            try {
                // We'll try to log it.
                xarLogMessage($msg);
            } catch(Exception $e) {
                // Oh well, forget it then
            }
            return; // no need to raise exception
        }

        //Newer php versions have a 5th parameter that will give us back the context
        //The variable values during the error...
        $msg = "At: " . $file." (Line: " . $line.")\n". $errorString;

        // Make cached files also display their source file if it's a template
        // This is just for convenience when giving support, as people will probably
        // not look in the CACHEKEYS file to mention the template.
        if(isset($GLOBALS['xarTpl_cacheTemplates'])) {
            $sourcetmpl='';
            $base = basename(strval($file),'.php');
            $varDir = xarCoreGetVarDirPath();
            if (file_exists($varDir . XARCORE_TPL_CACHEDIR .'/CACHEKEYS')) {
                $fd = fopen($varDir . XARCORE_TPL_CACHEDIR .'/CACHEKEYS', 'r');
                while($cache_entry = fscanf($fd, "%s\t%s\n")) {
                    list($hash, $template) = $cache_entry;
                    // Strip the colon
                    $hash = substr($hash,0,-1);
                    if($hash == $base) {
                        // Found the file, source is $template
                        $sourcetmpl = $template;
                        break;
                    }
                }
                fclose($fd);
            }
        }
        
        if(isset($sourcetmpl) && $sourcetmpl != '') $msg .= "\n\n[".$sourcetmpl."]";
        if (!function_exists('xarModURL')) {
            $rawmsg = "Normal Xaraya error processing has stopped because of an error encountered.\n\n";
            $rawmsg .= "The last registered error message is:\n\n";
            $rawmsg .= "PHP Error code: " . $errorType . "\n\n";
            $rawmsg .= $msg;
            $msg = $rawmsg;
        } else {
            if (xarRequest::$allowShortURLs && isset(xarRequest::$shortURLVariables['module'])) {
                $module = xarRequest::$shortURLVariables['module'];
                // Then check in $_GET
            } elseif (isset($_GET['module'])) {
                $module = $_GET['module'];
                // Nothing found, return void
            } else {
                $module = '';
            }
            $product = ''; $component = '';
            if ($module != '') {
                // load relative to the current file (e.g. for shutdown functions)
                include(dirname(__FILE__) . "/xarayacomponents.php");
                foreach ($core as $corecomponent) {
                    if ($corecomponent['name'] == $module) {
                        $component = $corecomponent['fullname'];
                        $product = "App - Core";
                        break;
                    }
                }
                if ($component != '') {
                    foreach ($apps as $appscomponent) {
                        if ($appscomponent['name'] == $module) {
                            $component = $appscomponent['fullname'];
                            $product = "App - Modules";
                        }
                    }
                }
            }
        }
        // Throw an exception to let the default handler handle the rest.
        throw new PHPException($msg,$errorType);
    }

    // Private methods
    private static function RenderRaw(Exception $e)
    {
        // TODO: how many assumptions can we make about the rendering capabilities of the client here?
        $out="<pre>";
        $out.= 'Error: '.$e->getCode().": ".get_class($e)."\n";
        $out.= $e->getMessage()."\n\n";
        $out.= $e->getTraceAsString();
        $out.= "</pre>";
        return $out;
    }
}
?>