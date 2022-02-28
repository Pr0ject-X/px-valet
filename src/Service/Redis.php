<?php

declare(strict_types=1);

namespace Pr0jectX\PxValet\Service;

use Pr0jectX\PxValet\DockerServiceBase;
use Symfony\Component\Console\Question\Question;

/**
 * Define the docker redis service.
 */
class Redis extends DockerServiceBase
{
    /**
     * {@inheritDoc}
     */
    public static function label(): string
    {
        return 'Redis';
    }

    /**
     * {@inheritDoc}
     */
    public static function image(): string
    {
        return 'redis';
    }

    /**
     * {@inheritDoc}
     */
    public static function group(): string
    {
        return static::DOCKER_GROUP_CACHING;
    }

    /**
     * {@inheritDoc}
     */
    public function definition(): array
    {
        $package = $this->packageName();
        $unique = $this->uniqueIdentifier();
        $config = $this->getConfiguration();

        return parent::definition() + [
            'ports' => ["{$config['port']}:6379"],
            'volumes' => [
                "{$unique}-{$package}-data:/data"
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function defaultConfiguration(): array
    {
        return [
          'port' => 6379
        ] + parent::defaultConfiguration();
    }

    /**
     * {@inheritDoc}
     */
    public function configurationQuestions(): array
    {
        $config = $this->getConfiguration();

        return parent::configurationQuestions() + [
            'port' => $this->setRequiredQuestion(new Question(
                $this->formatQuestion('Input redis port', $config['port']),
                $config['port']
            ))
        ];
    }
}
