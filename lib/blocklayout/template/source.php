<?php
/**
 * Representing blocklayout source templates
 *
 * @package blocklayout
 * @copyright The Digital Development Foundation, 2006-07-26
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @author Marcel van der Boom <mrb@hsdev.com>
**/

sys::import('blocklayout.template.compiled');

/**
 * Define an interface for the SourceTemplate class so we obey our own stuff.
**/
interface ISourceTemplate
{
    function &compile();
    function &execute();
}

/**
 * Class to model the source template
 *
 * @package blocklayout
 * @todo    decorate this with a Stream object so we can compile anything that is a stream.
**/
class SourceTemplate extends CompiledTemplate implements ISourceTemplate
{
    /**
     * compile a source template into templatecode
     *
     * @return string the compiled template code.
    **/
    public function &compile() 
    {
        assert('isset($this->fileName); /* No source to compile from */');
        sys::import('blocklayout.compiler');
        $blCompiler = xarBLCompiler::instance();
        $templateCode = $blCompiler->compileFile($this->fileName);

        // Replace useless php context switches.
        // This sometimes seems to improve rendering end speed, dunno, bytecacher dependent?
        // Typical improvement i bench is around 4-5%
        $templateCode = preg_replace(array('/\?>[\s\n]+<\?php/','/<\?php[\s\n]+\?>/','/\?>[\s]+<\?php/','/<\?php[\s]+\?>/'),
                                     array("\n","\n",' ',' '),$templateCode);
        return $templateCode;
    }
    
    public function &execute()
    {
        // not yet
    }
}
?>
