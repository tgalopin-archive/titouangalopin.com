<?php

/**
 * Represent an article
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class Article
{
    /**
     * @var string
     */
    public $slug;

    /**
     * @var string
     */
    public $title;

    /**
     * @var \DateTime
     */
    public $date;

    /**
     * @var array
     */
    public $tags;

    /**
     * @var boolean
     */
    public $comments;

    /**
     * @var string
     */
    public $intro;

    /**
     * @var string
     */
    public $content;

    /**
     * @var string
     */
    public $rawContent;

    /**
     * @var string
     */
    public $rawMetadata;

    /**
     * @var string
     */
    public $rawFile;
}
