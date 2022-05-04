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

use Composer\Composer;
use Composer\DependencyResolver\DefaultPolicy;
use Composer\DependencyResolver\LocalRepoTransaction;
use Composer\DependencyResolver\Pool;
use Composer\DependencyResolver\Request;
use Composer\DependencyResolver\Solver;
use Composer\DependencyResolver\SolverProblemsException;
use Composer\Filter\PlatformRequirementFilter\PlatformRequirementFilterFactory;
use Composer\Installer\InstallationManager;
use Composer\IO\IOInterface;
use Composer\Package\BasePackage;
use Composer\Package\Locker;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackage;
use Composer\Package\Version\VersionParser;
use Composer\Repository\InstalledRepository;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Repository\PathRepository;
use Composer\Repository\PlatformRepository;
use Composer\Repository\RepositoryInterface;
use Composer\Repository\RepositorySet;
use Composer\Repository\RootPackageRepository;
use Composer\Semver\Constraint\Constraint;
use Composer\Util\Filesystem;
use Composer\Util\Loop;
use Exception;
use React\Promise\PromiseInterface;

class LinkManager
{
    protected Filesystem $filesystem;

    protected Loop $loop;

    protected InstallationManager $installationManager;

    protected InstalledRepositoryInterface $installedRepository;
    private Composer $composer;
    private IOInterface $io;

    public function __construct(
        Filesystem $filesystem,
        Loop $loop,
        InstallationManager $installationManager,
        InstalledRepositoryInterface $installedRepository,
        Composer $composer,
        IOInterface $io
    ) {
        $this->filesystem = $filesystem;
        $this->loop = $loop;
        $this->installationManager = $installationManager;
        $this->installedRepository = $installedRepository;
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * Checks if the given package is linked.
     */
    public function isLinked(LinkedPackage $linkedPackage): bool
    {
        return $this->filesystem->isSymlinkedDirectory($linkedPackage->getInstallationPath()) ||
        $this->filesystem->isJunction($linkedPackage->getInstallationPath());
    }

    /**
     * Links the package into the vendor directory.
     */
    public function linkPackage(LinkedPackage $linkedPackage): void
    {
        /*
        var_dump(count($this->installedRepository->getPackages()));
        if (!is_null($linkedPackage->getOriginalPackage())) {
            $this->uninstall($linkedPackage->getOriginalPackage());
            $leftOvers = $this->installedRepository->search($linkedPackage->getOriginalPackage()->getName());

            // It could happen that we have alias left-overs here.
            var_dump($leftOvers);
        }

        $this->install($linkedPackage->getPackage());
        */

        /*
        $localRepo = $this->composer->getRepositoryManager()->getLocalRepository();
        $lockedRepo = $this->composer->getLocker()->getLockedRepository();
        $lockedRepo->addPackage($linkedPackage->getPackage());

        $this->io->warning('----------------------');
        $localRepoTransaction = new LocalRepoTransaction(
            $lockedRepo,
            $localRepo
        );

        $this->composer->getInstallationManager()->execute($localRepo, $localRepoTransaction->getOperations());
        */

        $root = $this->composer->getPackage();
        $requires = $root->getRequires();
        $devRequires = $root->getDevRequires();
        // Loop through both, replace with new package.
        // If not exists add package to requires
        $root->setRequires($requires);
        $root->setDevRequires($devRequires);

        // Build list witch  packages that are required from the locked repository
        $lockedRepository = $this->composer->getLocker()->getLockedRepository();
        $requires = [];
        foreach ($lockedRepository->getPackages() as $package) {
            $constraint = new Constraint('=', $package->getVersion());
            $constraint->setPrettyString($package->getPrettyVersion());
            if ($package->getName() === $linkedPackage->getName()) {
                continue;
            }

            $requires[$package->getName()] = $constraint;
        }

        $repositorySet = new RepositorySet(
            'dev',
            [],
            [],
            $this->composer->getPackage()->getReferences(),
            $requires
        );
        $request = new Request();
        $request->requireName($linkedPackage->getName(), new Constraint('=', 'dev-master'));
        $policy = new DefaultPolicy();
        $repositorySet->addRepository(new RootPackageRepository($this->composer->getPackage()));
        $repo = new PathRepository(['url' => $linkedPackage->getPath()], $this->io, $this->composer->getConfig());
        $repositorySet->addRepository($repo);
        $pool = $repositorySet->createPool($request, $this->io);
        $solver = new Solver($policy, $pool, $this->io);

        $this->io->warning('----------------------');

        try {
            $transaction = $solver->solve($request, PlatformRequirementFilterFactory::ignoreAll());
            $operations = $transaction->getOperations();
            foreach ($operations as $operation) {
                $this->io->write($operation->show(false));
            }
        } catch (SolverProblemsException $exception) {
            $this->io->write($exception->getPrettyString($repositorySet, $request, $pool, true));
        }

        /*
        $repositorySet = new RepositorySet('stable', [], []);
        $locked = $this->composer->getLocker()->getLockedRepository();
        $repo = new PathRepository(['url' => $linkedPackage->getPath()], $this->io, $this->composer->getConfig());
        $repo->addPackage($linkedPackage->getPackage());
        $repositorySet->addRepository($repo);
        $request = new Request();
        // $request->fixLockedPackage($linkedPackage->getPackage());
        foreach ($locked->getPackages() as $package) {
            $request->lockPackage($package);
        }

        $policy = new DefaultPolicy();
        $pool = $repositorySet->createPool($request, $this->io);
        $solver = new Solver($policy, $pool, $this->io);

        $this->io->warning('----------------------');

        try {
            $transaction = $solver->solve($request);
            $operations = $transaction->getOperations();
            var_dump(count($operations));
            foreach ($operations as $operation) {
                $this->io->write($operation->show(false));
            }
        } catch (SolverProblemsException $exception) {
            $this->io->write($exception->getPrettyString($repositorySet, $request, $pool, true));
        }
        */
        /*
        var_dump(count($this->installedRepository->getPackages()));
        //$this->installedRepository->addPackage($linkedPackage->getPackage());
        $pool = new Pool($this->installedRepository->getPackages());
        $lockedRepository = $this->composer->getLocker()->getLockedRepository();
        $lockedRepository->addPackage($linkedPackage->getPackage());
        $request = new Request();
        foreach ($pool->getPackages() as $package) {
            //$request->lockPackage($package);
        }
        $request->requireName($linkedPackage->getPackage()->getPrettyName(), new Constraint('==', 'dev-master'));

        // $request->fixLockedPackage($linkedPackage->getPackage());
        $this->io->write($pool->__toString());
        $solver = new Solver($policy, $pool, $this->io);
        try {
            $transaction = $solver->solve($request);
        }
        catch (SolverProblemsException $exception) {
            $this->io->write($exception->getPrettyString($lockedRepository, $request, $pool));
        }
        $operations = $transaction->getOperations();
        foreach ($operations as $operation) {
            $this->io->write($operation->show(false));
        }
        */
        $this->io->warning('----------------------');

        $this->install($linkedPackage->getPackage());
    }

    private function createRepositorySet(
        Locker $locker,
        PlatformRepository $platformRepo,
        RepositoryInterface $lockedRepository,
        RootPackage $rootPackage
    ): RepositorySet {
        $minimumStability = $locker->getMinimumStability();
        $stabilityFlags = $locker->getStabilityFlags();

        $requires = [];
        foreach ($lockedRepository->getPackages() as $package) {
            $constraint = new Constraint('=', $package->getVersion());
            $constraint->setPrettyString($package->getPrettyVersion());
            $requires[$package->getName()] = $constraint;
        }

        $fixedRootPackage = clone $rootPackage;
        $fixedRootPackage->setRequires([]);
        $fixedRootPackage->setDevRequires([]);

        $stabilityFlags[$rootPackage->getName()] = BasePackage::$stabilities[VersionParser::parseStability($rootPackage->getVersion())];

        $repositorySet = new RepositorySet($minimumStability, $stabilityFlags, [], $rootPackage->getReferences(), $requires);
        $repositorySet->addRepository(new RootPackageRepository($fixedRootPackage));
        $repositorySet->addRepository($platformRepo);

        return $repositorySet;
    }

    /**
     * Unlinks the package from the vendor directory.
     */
    public function unlinkPackage(LinkedPackage $linkedPackage): void
    {
        // Update the repository to the current situation
        if (!is_null($linkedPackage->getOriginalPackage())) {
            $this->installedRepository->removePackage($linkedPackage->getOriginalPackage());
        }
        $this->installedRepository->addPackage($linkedPackage->getPackage());

        $this->uninstall($linkedPackage->getPackage());
        if (!is_null($linkedPackage->getOriginalPackage())) {
            $this->install($linkedPackage->getOriginalPackage());
        }
    }

    protected function uninstall(PackageInterface $package): void
    {
        $installer = $this->installationManager->getInstaller($package->getType());
        try {
            $this->wait($installer->uninstall($this->installedRepository, $package));
        } catch (Exception $exception) {
            $this->wait($installer->cleanup('uninstall', $package));
            throw $exception;
        }

        $this->wait($installer->cleanup('uninstall', $package));
    }

    /**
     * Downloads and installs the given package
     * https://github.com/composer/composer/blob/2.0.0/src/Composer/Util/SyncHelper.php.
     */
    protected function install(PackageInterface $package): void
    {
        $installer = $this->installationManager->getInstaller($package->getType());

        try {
            $this->wait($installer->download($package));
            $this->wait($installer->prepare('install', $package));
            $this->wait($installer->install($this->installedRepository, $package));
        } catch (Exception $exception) {
            $this->wait($installer->cleanup('install', $package));
            throw $exception;
        }

        $this->wait($installer->cleanup('install', $package));
    }

    /**
     * Waits for promise to be finished.
     */
    protected function wait(?PromiseInterface $promise): void
    {
        if (!is_null($promise)) {
            $this->loop->wait([$promise]);
        }
    }
}
