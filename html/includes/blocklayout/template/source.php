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
 * Define an interface for the xarSourceTemplate class so we obey our own stuff.
**/
interface IxarSourceTemplate
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
class xarSourceTemplate extends xarCompiledTemplate implements IxarSourceTemplate
{
    public function __construct($fileName) 
    {
        parent::__construct($fileName);
    }

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

        $out = '';
        if(xarTpl_outputPHPCommentBlockInTemplates()) {
            // FIXME: this is weird stuff:
            // theme is irrelevant, date is seen in the filesystem, sourcefile in CACHEKEYS, why? it complicates the system a lot.
            $commentBlock = "<?php\n/*"
                          . "\n * Source:     " . $this->fileName         // redundant
                          . "\n * Theme:      " . xarTplGetThemeName()  // confusing (can be any theme now, its the theme during compilation, which is also shown on the above line)
                          . "\n * Compiled: ~ " . date('Y-m-d H:i:s T') // redundant
                          . "\n */\n?>\n";
            $out .= $commentBlock;
        }
        // Replace useless php context switches.
        // This sometimes seems to improve rendering end speed, dunno, bytecacher dependent?
        // Typical improvement i bench is around 4-5%
        $templateCode = preg_replace(array('/\?>[\s\n]+<\?php/','/<\?php[\s\n]+\?>/','/\?>[\s]+<\?php/','/<\?php[\s]+\?>/'),
                                     array("\n","\n",' ',' '),$templateCode);
        
        $out .= $templateCode;
        return $out;
    }
    
    public function &execute()
    {
        // not yet
    }
}
?>
