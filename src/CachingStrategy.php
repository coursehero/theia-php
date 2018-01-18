<?php

namespace Theia;

/**
 * Interface ICachingStrategy
 * @package Theia
 */
interface ICachingStrategy
{
    /**
     * @param string $componentLibrary
     * @param string $component
     * @param string $key
     * @return ?RenderResult
     */
    public function get(string $componentLibrary, string $component, string $key);

    /**
     * @param string $componentLibrary
     * @param string $component
     * @param string $key
     * @param RenderResult $renderResult
     */
    public function set(string $componentLibrary, string $component, string $key, RenderResult $renderResult);
}
