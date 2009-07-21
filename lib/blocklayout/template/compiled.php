<?php
/**
 * Representing blocklayout compiled templates
 *
 * @package blocklayout
 * @copyright The Digital Development Foundation, 2006
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @author Marcel van der Boom <mrb@hsdev.com>
**/

/**
 * Class to model a compiled template
 *
 * @package blocklayout
 * @todo    decorate this with a Stream object so we can compile anything that is a stream?
 * @todo    use a xarTemplate base class?
**/
class CompiledTemplate extends Object
{
    protected $fileName = null;   // where is it stored?
    private   $source   = null;   // source file
    private   $type     = null;

    public function __construct($fileName,$source=null,$type='module')
    {
        // @todo keep here?
        if (!file_exists(sys::code() . $fileName))  throw new FileNotFoundException($fileName); // we only do files atm
        $this->fileName = $fileName;
        $this->source   = $source;
        $this->type     = $type;
    }

    public function &execute(&$bindvars)
    {
        assert('isset($this->fileName); /* No source to execute from */');
        assert('file_exists($this->fileName); /* Compiled templated disappeared in mid air, race condition? */');
        assert('is_array($bindvars); /* Template data needs to be passed in as an array */');

        // Do we really need this?
        $bindvars['_bl_data'] =& $bindvars;

        // Make the bindvars known in the scope.
        extract($bindvars,EXTR_OVERWRITE);

        if($this->type=='page') set_exception_handler(array('ExceptionHandlers','bone'));

        // Executing means generating output, start a buffer for it
        ob_start();

        try {
            // If caching is enabled then cache it for subsequent reuse
            try {
                $caching = xarConfigVars::get(null, 'System.Core.Caching');
            } catch (Exception $e) {
                $caching = 0;
            }
            if ($caching) {
                // Set up a variable stream
                sys::import('xaraya.streams.variables');
                // This variable will hold the stream contents
                global $_compiler_output;

                // Have we already cached this template?
                if (!xarCore::isCached( 'template',$this->source)) {
                    // Get the compiled template from the template cache
                    $_compiler_output = file_get_contents($this->fileName);
                    // Stick it in the cache
                    xarCore::setCached( 'template',$this->source, $_compiler_output);
                } else {
                    // Retrieve the compiled template from cache
                    $_compiler_output = xarCore::getCached( 'template',$this->source);
                }

                $res = include("var://_compiler_output");
            } else {
                $res = include($this->fileName);
            }
            
        } catch (Exception $e) {
            // Any exception inside the compiled template invalidates our output from it.
            // Clear its buffer, and raise exactly that exception, letting the exception handlers
            // take care of the rest. nice, very nice :-)
            ob_end_clean();
            throw $e;
        }

        if(isset($this->source)) {
            $prelimOut = ob_get_contents();
            ob_end_clean();
            ob_start();
            // this outputs the template and deals with start comments accordingly.
            // @todo bring this in here, not pull in from xarTemplate
            echo xarTpl_outputTemplate($this->source, $prelimOut);
        }

        // Fetch output and clean buffer
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }
}
?>