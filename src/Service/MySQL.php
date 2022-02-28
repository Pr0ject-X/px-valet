<?php

declare(strict_types=1);

namespace Pr0jectX\PxValet\Service;

use Pr0jectX\PxValet\DockerServiceBase;
use Symfony\Component\Console\Question\Question;

/**
 * Define the docker mysql service.
 */
class MySQL extends DockerServiceBase
{
    /**
     * Define the default database port
     */
    protected const DEFAULT_DATABASE_PORT = 3306;

    /**
     * Define the default database name.
     */
    protected const DEFAULT_DATABASE_NAME = 'app';

    /**
     * Define the default database username.
     */
    protected const DEFAULT_DATABASE_USERNAME = 'app';

    /**
     * Define the default database password.
     */
    protected const DEFAULT_DATABASE_PASSWORD = 'root';

    /**
     * {@inheritDoc}
     */
    public static function label(): string
    {
        return 'MySQL';
    }

    /**
     * {@inheritDoc}
     */
    public static function image(): string
    {
        return 'mysql';
    }

    /**
     * {@inheritDoc}
     */
    public static function group(): string
    {
        return static::DOCKER_GROUP_DATABASE;
    }

    /**
     * {@inheritDoc}
     */
    public function definition(): array
    {
        $image = static::image();
        $config = $this->getConfiguration();

        return parent::definition() + [
            'ports' => ["{$config['port']}:3306"],
            'volumes' => [
                "{$image}-data:/var/lib/mysql"
            ],
            'environment' => $this->environment(),
        ];
    }

    /**
     * The docker service environment variables.
     *
     * @return array
     */
    protected function environment(): array
    {
        $config = $this->getConfiguration();

        return [
            'MYSQL_USER' => $config['username'],
            'MYSQL_PASSWORD' => $config['password'],
            'MYSQL_DATABASE' => $config['database'],
            'MYSQL_ROOT_PASSWORD' => 'root',
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function defaultConfiguration(): array
    {
        return [
            'port' => static::DEFAULT_DATABASE_PORT,
            'database' => static::DEFAULT_DATABASE_NAME,
            'username' => static::DEFAULT_DATABASE_USERNAME,
            'password' => static::DEFAULT_DATABASE_PASSWORD,
        ] + parent::defaultConfiguration();
    }

    /**
     * {@inheritDoc}
     */
    public function configurationQuestions(): array
    {
        $config = $this->getConfiguration();
        return parent::configurationQuestions() + [
            'database' => $this->setRequiredQuestion(new Question(
                $this->formatQuestion('Input database name', $config['database']),
                $config['database']
            )),
            'port' => $this->setRequiredQuestion(new Question(
                $this->formatQuestion('Input database port', $config['port']),
                $config['port']
            )),
            'username' => $this->setRequiredQuestion(new Question(
                $this->formatQuestion('Input database username', $config['username']),
                $config['username']
            )),
            'password' => ($this->setRequiredQuestion(new Question(
                $this->formatQuestion('Input database password', $config['password']),
                $config['password']
            )))->setHidden(true),
        ];
    }
}
