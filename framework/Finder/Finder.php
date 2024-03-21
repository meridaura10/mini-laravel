<?php

namespace Framework\Kernel\Finder;

use Framework\Kernel\Finder\Exceptions\DirectoryNotFoundException;
use Framework\Kernel\Finder\Iterator\FilenameFilterIterator;
use Framework\Kernel\Finder\Iterator\FileTypeFilterIterator;
use Framework\Kernel\Finder\Iterator\LazyIterator;
use Iterator;
use IteratorAggregate;
use IteratorIterator;
use RecursiveDirectoryIterator;

class Finder implements IteratorAggregate
{
    public const IGNORE_VCS_FILES = 1;

    public const IGNORE_DOT_FILES = 2;

    public const IGNORE_VCS_IGNORED_FILES = 4;

    private int $ignore = 0;

    private int $mode = 0;

    private array $names = [];

    private array $dirs = [];

    private array $iterators = [];

    protected array $notNames = [];

    public function __construct()
    {
        $this->ignore = static::IGNORE_VCS_FILES | static::IGNORE_DOT_FILES;
    }

    public static function create(): static
    {
        return new static();
    }

    public function files(): static
    {
        $this->mode = FileTypeFilterIterator::ONLY_FILES;

        return $this;
    }

    public function name(string|array $patterns): static
    {
        $this->names = array_merge($this->names, (array) $patterns);

        return $this;
    }

    public function in(string|array $dirs): static
    {
        $resolvedDirs = [];

        foreach ((array) $dirs as $dir) {
            if (is_dir($dir)) {
                $resolvedDirs[] = [$this->normalizeDir($dir)];
            } elseif ($glob = glob($dir, (\defined('GLOB_BRACE') ? \GLOB_BRACE : 0) | \GLOB_ONLYDIR | \GLOB_NOSORT)) {
                sort($glob);
                $resolvedDirs[] = array_map($this->normalizeDir(...), $glob);
            } else {
                throw new DirectoryNotFoundException(sprintf('The "%s" directory does not exist.', $dir));
            }
        }

        $this->dirs = array_merge($this->dirs, ...$resolvedDirs);
        return $this;
    }

    public function getIterator(): Iterator
    {
        if (! count($this->dirs) && ! count($this->iterators)) {
            throw new \LogicException('You must call one of in() or append() methods before iterating over a Finder.');
        }

        $iterator = new \AppendIterator();

        foreach ($this->dirs as $dir) {
            $iterator->append(new IteratorIterator(new LazyIterator(fn () => $this->searchInDirectory($dir))));
        }

        foreach ($this->iterators as $it) {
            $iterator->append($it);
        }

        return $iterator;
    }

    private function searchInDirectory(string $dir): Iterator
    {
        $flags = RecursiveDirectoryIterator::SKIP_DOTS;

        $iterator = new RecursiveDirectoryIterator($dir, $flags);

        if ($this->mode) {
            $iterator = new FileTypeFilterIterator($iterator, $this->mode);
        }

        if ($this->names || $this->notNames) {
            $iterator = new FilenameFilterIterator($iterator, $this->names, $this->notNames);
        }

        return $iterator;
    }

    private function normalizeDir(string $dir): string
    {
        if ($dir === '/') {
            return $dir;
        }

        $dir = rtrim($dir, '/'.\DIRECTORY_SEPARATOR);

        if (preg_match('#^(ssh2\.)?s?ftp://#', $dir)) {
            $dir .= '/';
        }

        return $dir;
    }
}
