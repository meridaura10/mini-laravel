<?php

namespace Framework\Kernel\View\Compilers;

use Framework\Kernel\Support\Arr;
use Framework\Kernel\Support\Collection;
use Framework\Kernel\Support\Str;

class BladeCompiler extends Compiler
{
    use Concerns\CompilesAuthorizations,
        Concerns\CompilesClasses,
        Concerns\CompilesComments,
        Concerns\CompilesComponents,
        Concerns\CompilesConditionals,
        Concerns\CompilesEchos,
        Concerns\CompilesErrors,
        Concerns\CompilesFragments,
        Concerns\CompilesHelpers,
        Concerns\CompilesIncludes,
        Concerns\CompilesInjections,
        Concerns\CompilesJson,
        Concerns\CompilesJs,
        Concerns\CompilesLayouts,
        Concerns\CompilesLoops,
        Concerns\CompilesRawPhp,
        Concerns\CompilesSessions,
        Concerns\CompilesStacks,
        Concerns\CompilesStyles,
        Concerns\CompilesTranslations,
        Concerns\CompilesUseStatements,
        ReflectsClosures;


    protected array $rawTags = ['{!!', '!!}'];

    protected array $contentTags = ['{{', '}}'];

    protected array $escapedTags = ['{{{', '}}}'];

    protected string $echoFormat = 'e(%s)';

    protected array $extensions = [];

    protected array $compilers = [
        // 'Comments',
        'Extensions',
        'Statements',
        'Echos',
    ];

    protected ?string $path = null;

    protected array $footer = [];

    public function compile(?string $path = null): void
    {
        if ($path) {
            $this->setPath($path);
        }

        if (! is_null($this->cachePath)) {
            $contents = $this->compileString($this->files->get($this->getPath()));

            if (! empty($this->getPath())) {
                $contents = $this->appendFilePath($contents);
            }

            $this->ensureCompiledDirectoryExists(
                $compiledPath = $this->getCompiledPath($this->getPath())
            );

            $this->files->put($compiledPath, $contents);
        }
    }
    protected function hasEvenNumberOfParentheses(string $expression): bool
    {
        $tokens = token_get_all('<?php '.$expression);

        if (Arr::last($tokens) !== ')') {
            return false;
        }

        $opening = 0;
        $closing = 0;

        foreach ($tokens as $token) {
            if ($token == ')') {
                $closing++;
            } elseif ($token == '(') {
                $opening++;
            }
        }

        return $opening === $closing;
    }

    protected function replaceFirstStatement(string $search,string $replace,string $subject,int $offset): array
    {
        $search = (string) $search;

        if ($search === '') {
            return $subject;
        }

        $position = strpos($subject, $search, $offset);

        if ($position !== false) {
            return [
                substr_replace($subject, $replace, $position, strlen($search)),
                $position + strlen($replace),
            ];
        }

        return [$subject, 0];
    }

    protected function compileStatement($match)
    {
        if (str_contains($match[1], '@')) {
            $match[0] = isset($match[3]) ? $match[1].$match[3] : $match[1];
        } elseif (isset($this->customDirectives[$match[1]])) {
            $match[0] = $this->callCustomDirective($match[1], Arr::get($match, 3));
        } elseif (method_exists($this, $method = 'compile'.ucfirst($match[1]))) {
            $match[0] = $this->$method(Arr::get($match, 3));
        } else {
            return $match[0];
        }

        return isset($match[3]) ? $match[0] : $match[0].$match[2];
    }

    public function stripParentheses($expression)
    {
        if (Str::startsWith($expression, '(')) {
            $expression = substr($expression, 1, -1);
        }

        return $expression;
    }


    protected function compileExtensions($value)
    {
        foreach ($this->extensions as $compiler) {
            $value = $compiler($value, $this);
        }

        return $value;
    }

    protected function appendFilePath(string $contents): string
    {
        $tokens = $this->getOpenAndClosingPhpTokens($contents);

        if ($tokens->isNotEmpty() && $tokens->last() !== T_CLOSE_TAG) {
            $contents .= ' ?>';
        }

        return $contents."<?php /**PATH {$this->getPath()} ENDPATH**/ ?>";
    }

    protected function getOpenAndClosingPhpTokens(string $contents): Collection
    {
        return collect(token_get_all($contents))
            ->pluck(0)
            ->filter(function ($token) {
                return in_array($token, [T_OPEN_TAG, T_OPEN_TAG_WITH_ECHO, T_CLOSE_TAG]);
            });
    }

    public function compileString($value)
    {
        [$this->footer, $result] = [[], ''];

//        foreach ($this->prepareStringsForCompilationUsing as $callback) {
//            $value = $callback($value);
//        }
//
//        $value = $this->storeUncompiledBlocks($value);
//
//        // First we will compile the Blade component tags. This is a precompile style
//        // step which compiles the component Blade tags into @component directives
//        // that may be used by Blade. Then we should call any other precompilers.
//        $value = $this->compileComponentTags(
//            $this->compileComments($value)
//        );
//
//        foreach ($this->precompilers as $precompiler) {
//            $value = $precompiler($value);
//        }
//
//        // Here we will loop through all of the tokens returned by the Zend lexer and
//        // parse each one into the corresponding valid PHP. We will then have this
//        // template as the correctly rendered PHP that can be rendered natively.
        foreach (token_get_all($value) as $token) {
            $result .= is_array($token) ? $this->parseToken($token) : $token;
        }
//
//        if (! empty($this->rawBlocks)) {
//            $result = $this->restoreRawContent($result);
//        }


        if (count($this->footer) > 0) {
            $result = $this->addFooters($result);
        }

        if (! empty($this->echoHandlers)) {
            $result = $this->addBladeCompilerVariable($result);
        }

        return str_replace(
            ['##BEGIN-COMPONENT-CLASS##', '##END-COMPONENT-CLASS##'],
            '',
            $result);
    }

    protected function addFooters(string $result): string
    {
        return ltrim($result, "\n")
            ."\n".implode("\n", array_reverse($this->footer));
    }

    protected function storeUncompiledBlocks($value)
    {
        if (str_contains($value, '@verbatim')) {
            $value = $this->storeVerbatimBlocks($value);
        }

        if (str_contains($value, '@php')) {
            $value = $this->storePhpBlocks($value);
        }

        return $value;
    }

    protected function compileStatements(string $template): string
    {
        preg_match_all('/\B@(@?\w+(?:::\w+)?)([ \t]*)(\( ( [\S\s]*? ) \))?/x', $template, $matches);

        $offset = 0;

        for ($i = 0; isset($matches[0][$i]); $i++) {
            $match = [
                $matches[0][$i],
                $matches[1][$i],
                $matches[2][$i],
                $matches[3][$i] ?: null,
                $matches[4][$i] ?: null,
            ];

            // Here we check to see if we have properly found the closing parenthesis by
            // regex pattern or not, and will recursively continue on to the next ")"
            // then check again until the tokenizer confirms we find the right one.
            while (isset($match[4]) &&
                Str::endsWith($match[0], ')') &&
                ! $this->hasEvenNumberOfParentheses($match[0])) {
                if (($after = Str::after($template, $match[0])) === $template) {
                    break;
                }

                $rest = Str::before($after, ')');

                if (isset($matches[0][$i + 1]) && Str::contains($rest.')', $matches[0][$i + 1])) {
                    unset($matches[0][$i + 1]);
                    $i++;
                }

                $match[0] = $match[0].$rest.')';
                $match[3] = $match[3].$rest.')';
                $match[4] = $match[4].$rest;
            }

            [$template, $offset] = $this->replaceFirstStatement(
                $match[0],
                $this->compileStatement($match),
                $template,
                $offset
            );
        }

        return $template;
    }
    protected function parseToken(array $token): string
    {
        [$id, $content] = $token;

        if ($id == T_INLINE_HTML) {
            foreach ($this->compilers as $type) {
                $content = $this->{"compile{$type}"}($content);
            }
        }

        return $content;
    }



    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }
}