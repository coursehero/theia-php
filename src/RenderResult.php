<?php

namespace Theia;

/**
 * Class RenderResult
 * @package Theia
 */
class RenderResult
{
    /** @var string */
    private $html;

    /** @var array */
    private $assets;

    /**
     * RenderResult constructor.
     * @param string $html
     * @param array $assets
     */
    public function __construct(string $html, array $assets)
    {
        $this->html = $html;
        $this->assets = $assets;
    }

    /**
     * @return string
     */
    public function getHtml(): string
    {
        return $this->html;
    }

    /**
     * @return array
     */
    public function getAssets(): array
    {
        return $this->assets;
    }
}
