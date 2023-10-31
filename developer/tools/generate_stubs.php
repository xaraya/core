<?php
/**
 * Generate stubs of Xaraya core files for phpstan etc.
 */
$ROOT_DIR = dirname(dirname(__DIR__));
require_once $ROOT_DIR.'/vendor/autoload.php';
sys::init();

/**
 * From nikic/php-parser pull request of WinterSilence at https://github.com/nikic/PHP-Parser/pull/866
 */
//namespace PhpParser\PrettyPrinter;

use PhpParser\PrettyPrinter\Standard;
use PhpParser\Node\Stmt;
use PhpParser\Node\FunctionLike;

/**
 * PHP stub printer.
 */
class Stub extends Standard
{
    protected function pStmt_ClassConst(Stmt\ClassConst $node): string
    {
        // Omit private class constant
        if ($node->isPrivate()) {
            return '';
        }

        return parent::pStmt_ClassConst($node);
    }

    protected function pStmt_Property(Stmt\Property $node): string
    {
        // Omit private property
        if ($node->isPrivate()) {
            return '';
        }

        return parent::pStmt_Property($node);
    }

    protected function pStmt_ClassMethod(Stmt\ClassMethod $node): string
    {
        // Omit private method
        if ($node->isPrivate()) {
            return '';
        }

        return $this->pAttrGroups($node->attrGroups)
             . $this->pModifiers($node->flags)
             . 'function ' . ($node->byRef ? '&' : '') . $node->name
             //. '(' . $this->pMaybeMultiline($node->params, $this->phpVersion->supportsTrailingCommaInParamList()) . ')'
             . '(' . $this->pMaybeMultiline($node->params) . ')'
             . (null !== $node->returnType ? ': ' . $this->p($node->returnType) : '')
             // Omit method body
             . (null !== $node->stmts ? ' {}' : ';');
    }

    protected function pStmt_Function(Stmt\Function_ $node): string
    {
        return $this->pAttrGroups($node->attrGroups)
             . 'function ' . ($node->byRef ? '&' : '') . $node->name
             //. '(' . $this->pMaybeMultiline($node->params, $this->phpVersion->supportsTrailingCommaInParamList()) . ')'
             . '(' . $this->pMaybeMultiline($node->params) . ')'
             . (null !== $node->returnType ? ': ' . $this->p($node->returnType) : '')
             // Omit function body
             . ' {}';
    }

    /**
     * @inheritDoc
     */
    protected function pComments(array $comments): string
    {
        // Get last comment
        return \str_replace(["\r\n", "\n"], $this->nl, end($comments)->getReformattedText());
    }

    /**
     * @inheritDoc
     */
    protected function pStmts(array $nodes, bool $indent = true): string
    {
        if ($indent) {
            $this->indent();
        }

        $result = '';
        foreach ($nodes as $node) {
            $pNode = $this->p($node);
            if ($pNode === '') {
                continue;
            }
            /**
            if (
                $node instanceof Stmt\Property
                || $node instanceof Stmt\ClassLike
                || $node instanceof FunctionLike
            ) {
                $docComment = $node->getDocComment();
                if ($docComment !== null) {
                    $result .= $this->nl . $this->pComments([$docComment]);
                }
            }
             */
            if ($node instanceof Stmt\Nop) {
                continue;
            }
            $result .= $this->nl . $pNode;
        }

        if ($indent) {
            $this->outdent();
        }

        return $result;
    }
}

use PhpParser\Error;
use PhpParser\ParserFactory;
use Symfony\Component\Finder\Finder;

$parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
//$prettyPrinter = new PrettyPrinter\Standard;
$prettyPrinter = new Stub();
$finder = Finder::create()
              ->files()
              ->name('*.php')
              ->ignoreVCS(true)
              ->in($ROOT_DIR.'/html/lib/xaraya/')
              ->exclude('legacy');
$todo = [];
foreach ($finder as $file) {
    $absoluteFilePath = $file->getRealPath();
    $stubFilePath = str_replace(
        [$ROOT_DIR.'/html/lib/xaraya/', '.php'],
        [__DIR__.'/stubs/', '.stub'],
        $absoluteFilePath
    );
    echo $stubFilePath . "\n";
    $code = $file->getContents();
    try {
        $ast = $parser->parse($code);
        $contents = $prettyPrinter->prettyPrintFile($ast);
        if (!is_dir(dirname($stubFilePath))) {
            mkdir(dirname($stubFilePath), 0777, true);
        }
        file_put_contents($stubFilePath, $contents);
        $todo[] = $stubFilePath;
    } catch (Error $error) {
        echo "Parse error: {$error->getMessage()}\n";
    }
}

$stubFilePath = __DIR__.'/stubs/bootstrap.stub';
echo $stubFilePath . "\n";
$code = file_get_contents($ROOT_DIR.'/html/bootstrap.php');
$ast = $parser->parse($code);
$contents = $prettyPrinter->prettyPrintFile($ast);
file_put_contents($stubFilePath, $contents);
$todo[] = $stubFilePath;

$stubFilePath = __DIR__.'/stubs.neon';
$contents = '
parameters:
    stubFiles:
';
foreach ($todo as $file) {
    $contents .= '        - ' . str_replace(__DIR__ . '/stubs/', 'stubs/', $file) . "\n";
}
file_put_contents($stubFilePath, $contents);
