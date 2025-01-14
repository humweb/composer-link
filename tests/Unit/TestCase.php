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

namespace Tests\Unit;

use Composer\Package\PackageInterface;
use Composer\Util\Filesystem;
use ComposerLink\LinkedPackage;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class TestCase extends PHPUnitTestCase
{
    protected string $tmpAbsoluteDir;

    protected string $tmpRelativeDir;

    protected Filesystem $filesystem;

    /** @SuppressWarnings(PHPMD.StaticAccess) */
    protected function setUp(): void
    {
        parent::setUp();

        $tmp = 'tests' . DIRECTORY_SEPARATOR . 'tmp';
        $this->filesystem = new Filesystem();
        $this->filesystem->emptyDirectory($tmp);

        $this->tmpAbsoluteDir = realpath($tmp) . DIRECTORY_SEPARATOR;
        $this->tmpRelativeDir = $tmp . DIRECTORY_SEPARATOR;
    }

    protected function tearDown(): void
    {
        $this->filesystem->removeDirectory($this->tmpAbsoluteDir);
        parent::tearDown();
    }

    /** @SuppressWarnings(PHPMD.BooleanArgumentFlag) */
    protected function mockPackage(string $name = 'package', bool $withOriginalPackage = true): LinkedPackage
    {
        $package = $this->createMock(LinkedPackage::class);
        $package->method('getName')->willReturn('test/' . $name);
        $package->method('getPath')->willReturn('../test-path-' . $name);
        $package->method('getInstallationPath')->willReturn('../install-path-' . $name);
        if ($withOriginalPackage) {
            $package->method('getOriginalPackage')
                ->willReturn($this->createMock(PackageInterface::class));
        }

        return $package;
    }
}
