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

namespace ComposerLink\Repository;

use ComposerLink\LinkedPackage;
use RuntimeException;

class Repository
{
    protected $transformer;

    protected $storage;

    /**
     * @var array<int, LinkedPackage>
     */
    protected $linkedPackages = [];

    public function __construct(StorageInterface $storage, Transformer $transformer)
    {
        $this->transformer = $transformer;
        $this->storage = $storage;

        $this->load();
    }

    public function store(LinkedPackage $linkedPackage)
    {
        $index = $this->findIndex($linkedPackage);

        if (is_null($index)) {
            $this->linkedPackages[] = clone $linkedPackage;

            return;
        }

        $this->linkedPackages[$index] = clone $linkedPackage;
    }

    /**
     * @return LinkedPackage[]
     */
    public function all(): array
    {
        $all = [];
        foreach ($this->linkedPackages as $package) {
            $all[] = clone $package;
        }

        return $all;
    }

    public function findByPath(string $path)
    {
        foreach ($this->linkedPackages as $linkedPackage) {
            if ($linkedPackage->getPath() === $path) {
                return clone $linkedPackage;
            }
        }

        return null;
    }

    public function findByName(string $name)
    {
        foreach ($this->linkedPackages as $linkedPackage) {
            if ($linkedPackage->getName() === $name) {
                return clone $linkedPackage;
            }
        }

        return null;
    }

    public function remove(LinkedPackage $linkedPackage)
    {
        $index = $this->findIndex($linkedPackage);

        if (is_null($index)) {
            throw new RuntimeException('Linked package not found');
        }

        array_splice($this->linkedPackages, $index, 1);
    }

    public function persist()
    {
        $data = [
            'packages' => [],
        ];
        foreach ($this->linkedPackages as $package) {
            $data['packages'][] = $this->transformer->export($package);
        }

        $this->storage->write($data);
    }

    private function load()
    {
        if (!$this->storage->hasData()) {
            return;
        }

        $data = $this->storage->read();

        foreach ($data['packages'] as $package) {
            $this->linkedPackages[] = $this->transformer->load($package);
        }
    }

    private function findIndex(LinkedPackage $package)
    {
        foreach ($this->linkedPackages as $index => $linkedPackage) {
            if ($linkedPackage->getName() === $package->getName()) {
                return $index;
            }
        }

        return null;
    }
}
