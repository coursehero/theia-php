<?php

namespace Theia;

/**
 * Interface CachingInterface
 * @package Theia
 */
interface CachingInterface
{
    /**
     * @param string $key
     * @return ?RenderResult
     */
    public function get(string $key);

    /**
     * @param string $componentLibrary
     * @param string $component
     * @param string $key
     * @param RenderResult $renderResult
     */
    public function set(string $componentLibrary, string $component, string $key, RenderResult $renderResult);
}
