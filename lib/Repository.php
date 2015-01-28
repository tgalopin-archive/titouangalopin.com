<?php

/**
 * Posts repository: retrieve posts from the file system
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class Repository
{
    /**
     * @var string
     */
    protected $root;

    /**
     * @var Formatter
     */
    protected $formatter;

    /**
     * @param string $root
     * @param Formatter $formatter
     */
    public function __construct($root, Formatter $formatter)
    {
        $this->root = $root;
        $this->formatter = $formatter;
    }

    /**
     * @return Article[]
     */
    public function findList()
    {
        $finder = new \Symfony\Component\Finder\Finder();
        $finder->files()->in($this->root)->name('*.md')->sort(function ($a, $b) {
            return strcmp($b->getRealpath(), $a->getRealpath());
        });

        $articles = [];

        /** @var \SplFileInfo $file */
        foreach ($finder as $file) {
            $articles[] = $this->formatter->formatHead($file);
        }

        return $articles;
    }

    /**
     * @param string $slug
     * @return Article
     */
    public function findOne($slug)
    {
        $pathname = $this->root . '/' . $slug . '.md';

        if (! file_exists($pathname) || ! is_readable($pathname)) {
            return false;
        }

        return $this->formatter->format(new \SplFileInfo($pathname));
    }
}
