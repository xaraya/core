<?php

/**
 * Representing blocklayout compiled templates
 *
 * @package blocklayout
 * @subpackage compiler
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Marcel van der Boom <mrb@hsdev.com>
**/
use Xaraya\Services\xar;

/**
 * Class to model a compiled template
 *
 * @package blocklayout
 * @todo    decorate this with a Stream object so we can compile anything that is a stream?
 * @todo    use a xarTemplate base class?
**/
class CompiledTemplate extends xarObject
{
    /** @var ?string */
    protected $fileName = null;   // where is it stored?
    /** @var ?string */
    private $source   = null;   // source file
    /** @var ?string */
    private $type     = null;

    /**
     * Summary of __construct
     * @param ?string $fileName
     * @param ?string $source
     * @param ?string $type
     */
    public function __construct($fileName, $source = null, $type = 'module')
    {
        // @todo keep here?
        //if (!file_exists($fileName))  throw new FileNotFoundException($fileName); // we only do files atm
        $this->fileName = $fileName;
        $this->source   = $source;
        $this->type     = $type;
    }

    /**
     * Summary of execute
     * @param array<string, mixed> $bindvars
     * @param mixed $caching
     * @return string
     */
    public function &execute(&$bindvars, $caching = 0)
    {
        assert(isset($this->fileName));
        assert(is_array($bindvars));

        // Do we really need this?
        $bindvars['_bl_data'] = & $bindvars;

        // Make the bindvars known in the scope.
        extract($bindvars, EXTR_OVERWRITE);

        if ($this->type == 'page') {
            xarDebug::setExceptionHandler(['ExceptionHandlers','bone']);
        }

        // Executing means generating output, start a buffer for it
        ob_start();

        try {
            // If caching is enabled then cache it for subsequent reuse
            // @todo check auto-loading this for stream_wrapper_register()
            if ($caching && class_exists('VariableStream', true)) {
                // Set up a variable stream
                // This variable will hold the stream contents
                global $_compiler_output;

                $mem = xar::mem();
                // Have we already cached this template?
                if (!$mem->has('template', $this->source)) {
                    // Get the compiled template from the template cache
                    $_compiler_output = file_get_contents($this->fileName);
                    // Stick it in the cache
                    $mem->set('template', $this->source, $_compiler_output);
                } else {
                    // Retrieve the compiled template from cache
                    $_compiler_output = $mem->get('template', $this->source);
                }

                $res = include("var://_compiler_output");
                // or simply eval('?[remove]>'.$_compiler_output); without streams
            } else {
                try {
                    $res = include($this->fileName);
                } catch (Exception $e) {
                    echo $e->getMessage();
                    xarCore::exit();
                    return false;
                }
            }

        } catch (Exception $e) {
            // Any exception inside the compiled template invalidates our output from it.
            // Clear its buffer, and raise exactly that exception, letting the exception handlers
            // take care of the rest. nice, very nice :-)
            ob_end_clean();
            throw $e;
        }

        if (isset($this->source)) {
            $prelimOut = ob_get_contents();
            ob_end_clean();
            ob_start();
            // this outputs the template and deals with start comments accordingly.
            // @todo bring this in here, not pull in from xarTemplate
            echo xar::tpl()->outputTemplate($this->source, $prelimOut);
        }

        // Fetch output and clean buffer
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }
}
