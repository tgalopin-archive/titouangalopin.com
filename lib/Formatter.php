<?php

use League\CommonMark\CommonMarkConverter;
use Symfony\Component\Yaml\Yaml;

/**
 * Post formatter: transform a Markdown file in an Article object
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class Formatter
{
    /**
     * @var CommonMarkConverter
     */
    protected $converter;

    /**
     * @param CommonMarkConverter $converter
     */
    public function __construct(CommonMarkConverter $converter)
    {
        $this->converter = $converter;
    }

    /**
     * @param SplFileInfo $file
     * @return Article
     */
    public function formatHead(\SplFileInfo $file)
    {
        $article = new Article();
        $article->slug = $file->getBasename('.' . $file->getExtension());
        $article->rawFile = file_get_contents($file->getPathname());

        $parts = explode('---', $article->rawFile);

        $article->rawMetadata = $parts[0];
        $article->rawContent = ltrim($parts[1]);

        $metadata = Yaml::parse($parts[0]);

        $article->title = $metadata['title'];
        $article->date = \DateTime::createFromFormat('U', $metadata['date']);
        $article->tags = $metadata['tags'];
        $article->comments = $metadata['comments'];
        $article->published = $metadata['published'];
        $article->intro = $this->converter->convertToHtml(trim($metadata['intro']));

        return $article;
    }

    /**
     * @param SplFileInfo $file
     * @return Article
     */
    public function format(\SplFileInfo $file)
    {
        $article = $this->formatHead($file);
        $article->content = $this->converter->convertToHtml($article->rawContent);

        return $article;
    }
}
