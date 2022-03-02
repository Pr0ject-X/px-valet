<?php

declare(strict_types=1);

namespace Pr0jectX\PxValet\Contracts;

/**
 * Define the docker service interface.
 */
interface DockerServiceInterface
{
    /**
     * The docker service label.
     *
     * @return string
     *   The docker service label.
     */
    public static function label(): string;

    /**
     * The docker service image name.
     *
     * @return string
     *   The docker service image name.
     */
    public static function image(): string;

    /**
     * The docker service group.
     *
     * @return string
     *   The docker service group.
     */
    public static function group(): string;

    /**
     * The docker service package name.
     *
     * @return string
     *   The docker service package name.
     */
    public function packageName(): string;

    /**
     * The docker service definition.
     *
     * @return array
     *   An array of parameters for the service definition.
     */
    public function definition(): array;

    /**
     * The docker service template directory.
     *
     * @return string
     *   The docker service template directory.
     */
    public function templateDirectory(): string;

    /**
     * The docker service configuration questions.
     *
     * @return \Symfony\Component\Console\Question\ChoiceQuestion[]
     *   An array of questions objects.
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function configurationQuestions(): array;
}
