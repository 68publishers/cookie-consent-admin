<?php

declare(strict_types=1);

namespace App;

use ArrayIterator;
use IteratorAggregate;
use Nette\Bootstrap\Configurator;
use SixtyEightPublishers\Environment\Bootstrap\EnvBootstrap;
use SixtyEightPublishers\Environment\Debug\EnvDetector;
use SixtyEightPublishers\Environment\Debug\SimpleCookieDetector;

final class Bootstrap
{
    public static function boot(): Configurator
    {
        $configurator = new Configurator();

        EnvBootstrap::bootNetteConfigurator($configurator, self::createDetectorsIterator());

        $configurator->enableTracy(__DIR__ . '/../var/log');
        $configurator->setTempDirectory(__DIR__ . '/../var');
        $configurator->addConfig(__DIR__ . '/../config/config.neon');

        if ('dev' === $_ENV['APP_ENV'] && '1' === $_ENV['APP_DEBUG']) {
            $configurator->addConfig(__DIR__ . '/../config/config.dev.neon');
        }

        if ('cli' === PHP_SAPI) {
            $configurator->addStaticParameters([
                'wwwDir' => dirname(__DIR__ . '/../public/index.php'),
            ]);
        }

        return $configurator;
    }

    private static function createDetectorsIterator(): iterable
    {
        return new class implements IteratorAggregate {
            public function getIterator(): ArrayIterator
            {
                $detectors = isset($_ENV['APP_DEBUG_COOKIE_SECRET']) ? [new SimpleCookieDetector($_ENV['APP_DEBUG_COOKIE_SECRET'], 'debug_please')] : [];
                $detectors[] = new EnvDetector();

                return new ArrayIterator($detectors);
            }
        };
    }
}
