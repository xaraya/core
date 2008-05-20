<?php
/**
 * BlockLayout Template Engine Compiler
 *
 * The compiler is responsible for compiling xar + xml -> php + xml
 *
 * @package blocklayout
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author Marco Canini <marco@xaraya.com>
 * @author Paul Rosania  <paul@xaraya.com>
 * @author Marcel van der Boom <marcel@xaraya.com>
 * @author Marty Vance <dracos@xaraya.com>
 * @author Garrett Hunter <garrett@blacktower.com>
 */

/* This one exception depends on BL being inside Xaraya, try to correct this later */
sys::import('xaraya.exceptions');
class BLCompilerException extends xarExceptions
{
}

class BLParserException extends BLCompilerException
{
}

/**
 * ParserError
 *
 * class to hold parser errors
 *
 * @package blocklayout
 * @access private
 * @throws BLParserException
 * @todo ML for the error message?
 */
class ParserError extends Exception
{
    function raiseError($type, $msg)
    {
        $out  = "Template error in file '#(1)' at line #(2), column #(3):\n\n";
        $out .= $msg."\n\n";
        $out .= "Line contents before the parsing error occurred:\n";
        $out .= "#(4) <== Error position\n";
        $vars = array($this->fileName,$this->line,$this->column,$this->lineText);
        // throw a generic exception for now, this probably should not do this, but i dunno yet
        throw new BLParserException($vars,$out);
    }
}

/**
 *  Interface definition for the blocklayout compiler, these are the things
 *  it offers, no more, no less
 *
 */
interface IxarBLCompiler
{
    static function &instance();        // Get an instance of the compiler
    function compileFile($fileName);    // compile a file
    function compileString(&$data);     // compile a string
}

/**
 * xarBLCompiler - the abstraction of the BL compiler
 *
 * The compiler holds the parser and the code generator as objects
 *
 * @package blocklayout
 * @access public
 */
class xarBLCompiler extends Object implements IxarBLCompiler
{
    private static $instance = null;
    public $parser;

    /**
     * Private constructor, since this is a Singleton
     */
    private function __construct()
    {
        $this->parser = new TemplateParser();
    }

    /**
     * Implementation of the interface
     */
    public static function &instance()
    {
        if(self::$instance == null) {
            self::$instance = new xarBLCompiler();
        }
        return self::$instance;
    }

    public function compileString(&$data)
    {
        return $this->compile($data);
    }

    public function compileFile($fileName)
    {
        // The @ makes the code better to handle, leave it.
        if (!($fp = @fopen($fileName, 'r'))) {
            throw new BLCompilerException($fileName,"Cannot open template file '#(1)'");
        }

        if ($fsize = filesize($fileName)) {
            $templateSource = fread($fp, $fsize);
        } else {
            $templateSource = '';
            while (!feof($fp)) {
                $templateSource .= fread($fp, 4096);
            }
        }

        fclose($fp);
        sys::import('xaraya.log');
        xarLogMessage("BL: compiling $fileName");

        $this->parser->setFileName($fileName);
        $res = $this->compile($templateSource);
        return $res;
    }

    /**
     * Private methods
     */
    private function compile(&$templateSource)
    {
        sys::import('blocklayout.xsltransformer');
        $xslFile = sys::root() . '/lib/blocklayout/xslt/xar2php.xsl';
        $xslProc = new BlockLayoutXSLTProcessor($xslFile);
        // This is confusing, dont do this here.
        $xslProc->xmlFile = $this->parser->getFileName();
        // This generates php code, the documentree is not visible here anymore
        $outDoc = $xslProc->transform($templateSource);
        return $outDoc;
    }
}

/**
 * TemplatePositionInfo
 *
 * Instance of this class record where we are doing what in the templates
 *
 * @package blocklayout
 * @access private
 */
class TemplateParser extends ParserError
{
    public $fileName = '';

    function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }
    function getFileName()
    {
        return $this->fileName;
    }
}

/**
 * ExpressionTransformer
 *
 * Transforms BL and php expressions from templates.
 *
 * @package blocklayout
 * @access private <-- Not true, public as hell
 * @todo   make protected, should only be called from tag handler no?
 * @todo   split up in PHP and BL parts with one interface method (transform)
 */
class ExpressionTransformer extends Object
{
    const varToken = '$';
    const ciDelim = '#';

    /*
     * Replace the array and object notation.
     * This is the BLExpression grammar:
     * BLExpression ::= Variable | Variable '.' ArrayKey | Variable ':' Property
     * Variable ::= [a-zA-Z_] ([0-9a-zA-Z_])*
     * ArrayKey ::= Name | Name '.' ArrayKey | Name ':' Property
     * Property ::= Name | Name '.' ArrayKey | Name ':' Property
     * Name     ::= ([0-9a-zA-Z_])+
     */
    static function transformBLExpression($blExpression)
    {
        $blExpression = self::normalize($blExpression);

        // 'resolve' the dot and colon notation
        $subparts = preg_split('/[\[|\]]/', $blExpression);
        if(count($subparts) > 1) {
            foreach($subparts as $subpart) {
                // Resolve the subpart
                $blExpression = str_replace($subpart, self::transformBLExpression($subpart), $blExpression);
            }
            return $blExpression;
        }

        $identifiers = preg_split('/[.|:]/',$blExpression);
        $operators = preg_split('/[^.|^:]/',$blExpression,-1,PREG_SPLIT_NO_EMPTY);

        $numIdentifiers = count($identifiers);

        $expression = $identifiers[0];
        for ($i = 1; $i < $numIdentifiers; $i++) {
            if($operators[$i - 1] == '.') {
                if((substr($identifiers[$i],0,1) == self::varToken) || is_numeric($identifiers[$i])) {
                    $expression .= "[".$identifiers[$i]."]";
                } else {
                    $expression .= "['".$identifiers[$i]."']";
                }
            } elseif($operators[$i - 1] == ':') {
                $expression .= '->'.$identifiers[$i];
            }
        }
        return $expression;
    }

    /**
     * Transform a PHP expression from a template to a valid piece of PHP code
     *
     * @return string Valid PHP expression
     * @todo if expressions were always between #...# this would be easier
     * @todo if the key / objectmember is a variable, make sure it fits the regex for a valid variable name
     * @todo the convenience operators may conflict in some situations with the MLS ( like 'le' for french)
     **/
    static function transformPHPExpression($phpExpression)
    {
        $phpExpression =self::normalize($phpExpression);
        // This regular expression matches variables in their notation as
        // supported by php  and according to the dot/colon grammar in the
        // method above. These expressions are matched and passed on to the BL
        // expression resolver above which resolves them into php variables notation.
        // The resolved names are replaced in the original expression

        // Let's dissect the expression so it's a bit more clear:
        //  1. /..../i            => we're matching in a case - insensitive  way what's between the /-es (FIXME: KEEP AN EYE ON THIS)
        //  2. \\\$               => matches \$ which is an escaped $ in the string to match
        //  3. (                  => this starts a captured subpattern
        //  4.  [a-z_]            => matches a letter or underscore, which is wat vars need to start with
        //  5.  [0-9a-z_\[\]\$]*  => matches the rest of the variables which might be present, while preserving [ and ]
        //  6.  (                 => start property / array access subpattern
        //  7.   :|\\.            => matches the colon or the dot notation
        //  8.   [$]{0,1}         => the array key or object member may be a variable
        //  9.   [0-9a-z_\]\[\$]+ => matches number,letter or underscore, one or more occurrences
        // 10.  )                 => matches right brace
        // 11.  *                 => match zero or more occurences of the property access / array key notation (colon notation)
        // 12. )                  => ends the current pattern
        // NOTE: The behaviour of this method along with the BLExpression method above CHANGED. Part
        //       of the resolving is now done by the previous method (i.e. a complete expression is passed into it)

        $regex = "/((\\\$[a-z_][a-z0-9_\[\]\$]*)([:|\.][$]{0,1}[0-9a-z_\]\[\$]+)*)/i";
        if (preg_match_all($regex, $phpExpression,$matches)) {
            // Resolve BL expressions inside the php Expressions

            // To prevent overlap as much as we can we sort descending by length
            usort($matches[0], array('ExpressionTransformer','rlensort'));
            $numMatches = count($matches[0]);
            for ($i = 0; $i < $numMatches; $i++) {
                // CHECKME: & removed here for php 4.4
                $resolvedName = self::transformBLExpression($matches[0][$i]);
                if (!isset($resolvedName)) return; // throw back

                // CHECK: Does it matter if there is overlap in the matches?
                $phpExpression = str_replace($matches[0][$i], $resolvedName, $phpExpression);
            }
        }

        $findLogic      = array(' eq ', ' ne ', ' lt ', ' gt ', ' id ', ' nd ', ' le ', ' ge ');
        $replaceLogic   = array(' == ', ' != ',  ' < ',  ' > ', ' === ', ' !== ', ' <= ', ' >= ');

        $phpExpression = str_replace($findLogic, $replaceLogic, $phpExpression);

        return $phpExpression;
    }

    static function rlensort($a, $b)
    {
        if(strlen($a) == strlen($b)) {
            return 0;
        }
        return (strlen($a) < strlen($b)) ? 1 : -1;
    }

    static function normalize($expr)
    {
        /* If the expression is enclosed in # s, ignore them */
        if(empty($expr)) return $expr;
        if( $expr{0} == self::ciDelim &&
            $expr{strlen($expr)-1} == self::ciDelim) {
            $expr = substr($expr,1,-1);
        }
        return $expr;
    }
}
?>
