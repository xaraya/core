<?php
/**
 * Representing blocklayout source templates in Xaraya
 *
 * @package core\templating
 * @subpackage templating
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @author Marcel van der Boom <mrb@hsdev.com>
**/

sys::import('blocklayout.template.source');

/**
 * Class to model the source template
 *
 * @todo    decorate this with a Stream object so we can compile anything that is a stream.
**/
class XarayaSourceTemplate extends SourceTemplate
{
    /**
     * compile a source template into templatecode
     *
     * @return string the compiled template code.
    **/
    public function &compile() 
    {
        assert('isset($this->fileName); /* No source to compile from */');
        sys::import('xaraya.templating.compiler');
        $compiler = XarayaCompiler::instance();
        $templateCode = $compiler->compileFile($this->fileName);

        $out = '';
        if(xarTpl::outputPHPCommentBlockInTemplates()) {
            // FIXME: this is weird stuff:
            // theme is irrelevant, date is seen in the filesystem, sourcefile in CACHEKEYS, why? it complicates the system a lot.
            $commentBlock = "<?php\n/*"
                          . "\n * Source:     " . $this->fileName         // redundant
                          . "\n * Theme:      " . xarTpl::getThemeName()  // confusing (can be any theme now, it's the theme during compilation, which is also shown on the above line)
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
}
?>