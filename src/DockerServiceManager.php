<?php

declare(strict_types=1);

namespace Pr0jectX\PxValet;

use Pr0jectX\PxValet\Contracts\DockerServiceInterface;
use Pr0jectX\PxValet\Service\MailHog;
use Pr0jectX\PxValet\Service\MariaDB;
use Pr0jectX\PxValet\Service\MySQL;
use Pr0jectX\PxValet\Service\Redis;

/**
 * Define the docker service manager.
 */
class DockerServiceManager
{
    /**
     * Define the available services.
     *
     * @return string[]
     *   An array of docker-based services.
     */
    protected function defineServices(): array
    {
        return [
            Redis::image() => Redis::class,
            MySQL::image() => MySQL::class,
            MariaDB::image() => MariaDB::class,
            MailHog::image() => MailHog::class,
        ];
    }

    /**
     * Create the docker service instance.
     *
     * @param string $image
     *   Docker service image.
     * @param array $configuration
     *   Docker service configuration.
     *
     * @return \Pr0jectX\PxValet\Contracts\DockerServiceInterface|null
     */
    public function createInstance(
        string $image,
        array $configuration = []
    ): ?DockerServiceInterface {
        if ($service = $this->defineServices()[$image]) {
            return new $service($configuration);
        }

        return null;
    }

    /**
     * The available docker service options.
     *
     * @return array
     *   An array of docker service options.
     */
    public function serviceOptions(string $group = null): array
    {
        $options = [];

        foreach ($this->defineServices() as $service) {
            if (isset($group) && $service::group() !== $group) {
                continue;
            }
            $options[$service::image()] = $service::label();
        }

        return $options;
    }
}
