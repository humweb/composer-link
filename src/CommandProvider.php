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

use Composer\Command\BaseCommand;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider as ComposerCommandProvider;
use ComposerLink\Commands\LinkCommand;
use ComposerLink\Commands\LinkedCommand;
use ComposerLink\Commands\UnlinkCommand;

class CommandProvider implements ComposerCommandProvider
{
    protected $io;

    protected $plugin;

    /**
     * @param array<string, mixed> $arguments
     */
    public function __construct(array $arguments)
    {
        $this->io = $arguments['io'];
        $this->plugin = $arguments['plugin'];
    }

    /**
     * @return BaseCommand[]
     */
    public function getCommands(): array
    {
        $this->io->debug("[ComposerLink]\tInitializing commands.");

        return [
            new LinkCommand($this->plugin),
            new UnlinkCommand($this->plugin),
            new LinkedCommand($this->plugin),
        ];
    }
}
