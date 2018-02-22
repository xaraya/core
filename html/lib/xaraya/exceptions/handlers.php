<?php
/**
 * Exception handlers class
 *
 * @package core\exceptions
 * @subpackage exceptions
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Marcel van der Boom <marcel@hsdev.com>
 * @todo This design promotes method growth instead of inheritance, change it 
 *         (there should be a chain of classes, not a chain of methods)
**/

interface IExceptionHandlers
{
    public static function defaulthandler(Exception $e);
    public static function phperrors($errorType, $errorString, $file, $line, array $errorContext=array());
}

/**
 * @package core\exceptions
 * @subpackage exceptions
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
**/
class ExceptionHandlers extends Object implements IExceptionHandlers
{
    private static $data = array();

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
     * @throws Exception
     * @todo Get rid of the redundant parts
     * @return void
     */
    // Handler for displaying default
    public static function defaulthandler(Exception $e)
    {
        // Make an attempt to render the page, hoping we have everything in place still
        // CHECKME: Hmm, is this a problem, as we're already in a handler?
        //          If it is not, then we really only have to consider really fatal errors
        //          (which wont get caught by any handler) and make sure the rest ends up
        //          in the bone handler
        try {
            // Try to get the full path location out of the trace
            self::$data = array_merge( self::$data,
                                  array(
                                  'major'     => 'MAJOR TBD (Code was: '. $e->getCode().')',
                                  'type'      => get_class($e), // consider stripping of 'Exception'
                                  'title'     => get_class($e) . ' ['.$e->getCode().'] was raised (native)',
                                  'short'     => htmlspecialchars($e->getMessage()),
                                  'line'      => $e->getLine(),
                                  'file'      => $e->getFile(),
                                ));
            // If we have em, use em

            if(method_exists('xarTpl','getThemeDir') && method_exists('xarTpl','file')) {
                $theme_dir = xarTpl::getThemeDir(); $template="systemerror";
                if(file_exists($theme_dir . '/modules/base/message-' . $template . '.xt')) {
                    $msg = xarTpl::file($theme_dir . '/modules/base/message-' . $template . '.xt', self::$data);
                } else {
                    $msg = xarTpl::file(sys::code() . 'modules/base/xartemplates/message-' . $template . '.xt', self::$data);
                }
                echo xarTpl::renderPage($msg);
            } else {
                // Rethrow it, we cant handle it.
                throw $e;
            }
        } catch( Exception $e_internal) {
            // Oh well, pick up the bones, but pick them up from the original exception
            // otherwise the message can be rather confusing
            // @todo: do we care about what $e_internal is?
            ExceptionHandlers::bone($e);
        }
    }

    // Handler with more information, for instance for the designated site admin
    public static function debughandler(Exception $e)
    {
        $trace = str_replace(sys::root(),'',$e->getTraceAsString());
        self::$data = array_merge( self::$data,
                              array(
                              'long'      => 'LONG msg TBD',
                              'hint'      => (method_exists($e,'getHint'))? htmlspecialchars($e->getHint()) : 'No hint available',
                              'stack'     => htmlspecialchars($trace),
                              'product'   => 'Product TBD',
                              'component' => 'Component TBD')
                            );
        self::defaulthandler($e);
    }

    // Lowest level handler, should always work, no assumptions whatsoever
    public static function bone(Exception $e)
    {
        echo ExceptionHandlers::RenderRaw($e);
    }

    /**
     * PHP error handler bridge to Xaraya exceptions
     *
     * @param  integer $errorType level of the error raised by PHP
     * @param  string  $errorString errormessage issued
     * @param  string  $file file is which the error occurred
     * @param  integer $line linenumber on which the error occurred
     * @param  array   $errorContext information on the context of the error
     * @author Marco Canini <marco@xaraya.com>
     * 
     * @throws PHPException
     * @return void
     */
    final public static function phperrors($errorRaised, $errorString, $file, $line, array $errorContext = array())
    {
        //Checks for a @ presence in the given line, should stop from setting Xaraya errors
        $oldLevel = error_reporting();
        try {
            // We'll try to get the configured threshold
            $errThreshold = xarSystemVars::get(sys::CONFIG,'Exception.ErrorLevel');
        } catch(Exception $e) {
            // Oh well, show everything so construct the maximum bitmask
            // Note that E_ALL is already a summed bitmask value (2047) while E_STRICT is *NOT* (2048)
            // MrB: if there are actually E_STRICT errors, this is known to break *some* installs ( mine ;-) )
            $errThreshold = E_STRICT + E_ALL;
        }
        // Only continue rendering if:
        // 1. the level was not 0 (either explicitly set or due to an @ on the line causing the error)
        // 2. the raised Errorlevel is included in the threshold bitmask
        if ( ($oldLevel == 0) or ($errorRaised & $errThreshold != $errorRaised )) {
            // Log the message so it is not lost.
            // TODO: make this message available to calling functions that suppress errors through '@'.
            $msg = "PHP error code $errorRaised at line $line of $file: $errorString";
            try {
                // We'll try to log it.
                xarLog::message($msg, xarLog::LEVEL_ERROR);
            } catch(Exception $e) {
                // Oh well, forget it then
            }
            return true; // no need to raise exception
        }

        // Make cached files also display their source file if it's a template
        // This is just for convenience when giving support, as people will probably
        // not look in the CACHEKEYS file to mention the template.
        $key = basename(strval($file),'.php');
        sys::import('xaraya.caching.template');
        $sourceFile = xarTemplateCache::sourceFile($key);

        // Construct the msg in a table like way, so it's easily copy/pasteable
        $spacer= str_repeat(' ',11);
        $msg = "File     : $file\n";
        if(isset($sourcefile)) {
        $msg.= $spacer."[$sourceFile]\n";
        }
        $msg.= "Line     : $line\n";
        $msg.= "Code     : $errorRaised\n";
        $msg.= "Message  : ".str_replace("\n","\n$spacer",wordwrap($errorString,75,"\n"))."\n";

        // Show variables only if this configvar is set in the themes backend
        // Default is no
        $show = false;
        if (class_exists('xarConfigVars')) {
            try {
                $show = xarConfigVars::get(null, 'Site.BL.ExceptionDisplay');
            } catch (Exception $e) {}
        }
        if ($show) {
            $msg.= "Variables: ";
            foreach($errorContext as $varName => $varValue) {
                $msg .= "\$$varName:\n$spacer  ". str_replace("\n","\n$spacer  ",htmlspecialchars(print_r($varValue,true)))."\n$spacer";
            }
        }

        if (!function_exists('xarModURL')) {
            $rawmsg = "Normal Xaraya error processing has stopped because of an error encountered.\n\n";
            $rawmsg .= "The last registered error message is:\n\n";
            $rawmsg .= $msg;
            $msg = $rawmsg;
        } else {
            $module = '';
            if (xarController::$allowShortURLs && isset(xarController::$shortURLVariables['module'])) {
                $module = xarController::$shortURLVariables['module'];
            } elseif (isset($_GET['module'])) {
                // Then check in $_GET
                $module = $_GET['module'];
            }

            // @todo consider removing this, it doesnt add much and causes quite a maintenance task
            $product = ''; $component = '';
            if ($module != '') {
                // load relative to the current file (e.g. for shutdown functions)
                sys::import('xaraya.exceptions.xarayacomponents');
                foreach (xarComponents::$core as $corecomponent) {
                    if ($corecomponent['name'] == $module) {
                        $component = $corecomponent['fullname'];
                        $product = "App - Core";
                        break;
                    }
                }
                if ($component != '') {
                    foreach (xarComponents::$apps as $appscomponent) {
                        if ($appscomponent['name'] == $module) {
                            $component = $appscomponent['fullname'];
                            $product = "App - Modules";
                        }
                    }
                }
            }

        }
        // Throw an exception to let the default handler handle the rest.
        throw new PHPException($msg,$errorRaised);
    }

    // Private methods
    private static function RenderRaw(Exception $e)
    {
        // @todo how many assumptions can we make about the rendering capabilities of the client here?
        $out="<pre>";
        $out.= 'Error: '.$e->getCode().": ".get_class($e)."\n";
        $out.= $e->getMessage()."\n";
        $out.= "Backtrace: ".str_replace("\n","\n           ",$e->getTraceAsString());
        $out.= "</pre>";
        return $out;
    }
}
?>