<?php declare(strict_types=1);

/*
 * This file is part of the composer-link plugin.
 *
 * Copyright (c) 2021-2022 Sander Visser <themastersleader@hotmail.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @link https://github.com/SanderSander/composer-link
 */

namespace ComposerLink\Commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LinkCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('link');
        $this->setDescription('Link a package to a local directory');
        $this->addArgument('path', InputArgument::REQUIRED, 'The path of the package');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $input->getArgument('path');

        $linkedPackage = $this->plugin->getPackageFactory()->fromPath($path);

        if (!is_null($this->plugin->getRepository()->findByPath($path))) {
            throw new \RuntimeException(sprintf('Package in path "%s" already linked', $path));
        }

        if ($currentLinked = $this->plugin->getRepository()->findByName($linkedPackage->getName())) {
            throw new \RuntimeException(sprintf(
                'Package "%s" already linked from path "%s"',
                $linkedPackage->getName(),
                $currentLinked->getPath()
            ));
        }

        $this->plugin->getRepository()->store($linkedPackage);
        $this->plugin->getRepository()->persist();
        $this->plugin->getLinkManager()->linkPackage($linkedPackage);

        return 0;
    }
}
