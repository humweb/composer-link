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

use Composer\Package\CompletePackage;
use Composer\Package\PackageInterface;

class LinkedPackage
{
    protected $path;

    protected $package;

    protected  $originalPackage;

    protected $installationPath;

    public function __construct(
        string $path,
        CompletePackage $package,
        PackageInterface $originalPackage = null,
        string $installationPath
    ) {
        $this->path = $path;
        $this->package = $package;
        $this->originalPackage = $originalPackage;
        $this->installationPath = $installationPath;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getName(): string
    {
        return $this->package->getName();
    }

    public function getPackage(): CompletePackage
    {
        return $this->package;
    }

    public function getOriginalPackage()
    {
        return $this->originalPackage;
    }

    public function getInstallationPath(): string
    {
        return $this->installationPath;
    }

    public function setOriginalPackage(PackageInterface $package = null)
    {
        $this->originalPackage = $package;
    }
}
