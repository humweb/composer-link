<?php

declare(strict_types=1);

/*
 * This file is part of the composer-link plugin.
 *
 * Copyright (c) 2021-2022 Sander Visser <themastersleader@hotmail.com>.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 *
 * @link https://github.com/SanderSander/composer-link
 */

namespace ComposerLink;

use InvalidArgumentException;

class PathHelper
{
    protected $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function isWildCard(): bool
    {
        return substr($this->path, -2) === DIRECTORY_SEPARATOR . '*';
    }

    /**
     * @return PathHelper[]
     */
    public function getPathsFromWildcard(): array
    {
        /** @var string[] $entries */
        $entries = glob($this->path, GLOB_ONLYDIR);
        $helpers = [];
        foreach ($entries as $entry) {
            if (!file_exists($entry . DIRECTORY_SEPARATOR . 'composer.json')) {
                continue;
            }

            $helpers[] = new PathHelper($entry);
        }

        return $helpers;
    }

    public function toAbsolutePath(string $workingDirectory): PathHelper
    {
        $path = $this->isWildCard() ? substr($this->path, 0, -1) : $this->path;
        $real = realpath($workingDirectory . DIRECTORY_SEPARATOR . $path);

        if ($real === false) {
            throw new InvalidArgumentException(
                sprintf('Cannot resolve absolute path to %s.', $path)
            );
        }

        if ($this->isWildCard()) {
            $real .= DIRECTORY_SEPARATOR . '*';
        }

        return new PathHelper($real);
    }

    public function getNormalizedPath(): string
    {
        if (substr($this->path, -1) === DIRECTORY_SEPARATOR) {
            return substr($this->path, 0, -1);
        }

        return $this->path;
    }
}
