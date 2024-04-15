<?php

namespace Framework\Kernel\View\Compilers;

use Framework\Kernel\Support\Collection;
use Framework\Kernel\Support\Str;

class BladeCompiler extends Compiler
{


    protected array $rawTags = ['{!!', '!!}'];

    protected array $contentTags = ['{{', '}}'];

    protected array $escapedTags = ['{{{', '}}}'];

    protected string $echoFormat = 'e(%s)';

    protected array $compilers = [
        // 'Comments',
//        'Extensions',
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

        if ($this->cachePath && $file = $this->files->get($this->getPath())) {
            $contents = $this->compileString($file);

            $contents = $this->appendFilePath($contents);

            $this->ensureCompiledDirectoryExists(
                $compiledPath = $this->getCompiledPath($this->getPath())
            );

            $this->files->put($compiledPath, $contents);
        }
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

    public function compileString(string $value): string
    {
        [$this->footer, $result] = [[], ''];

        foreach (token_get_all($value) as $token) {
            $result .= is_array($token) ? $this->parseToken($token) : $token;
        }



        return str_replace(
            ['##BEGIN-COMPONENT-CLASS##', '##END-COMPONENT-CLASS##'],
            '',
            $result);
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