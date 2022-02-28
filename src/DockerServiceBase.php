<?php

declare(strict_types=1);

namespace Pr0jectX\PxValet;

use Pr0jectX\Px\PxApp;
use Pr0jectX\PxValet\Contracts\DockerServiceInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Define the base docker service class.
 */
abstract class DockerServiceBase implements DockerServiceInterface
{
    use ConsoleQuestionTrait;

    /**
     * Define the docker other group.
     */
    public const DOCKER_GROUP_OTHER = 'other';

    /**
     * Define the docker caching group.
     */
    public const DOCKER_GROUP_CACHING = 'caching';

    /**
     * Define the docker database group.
     */
    public const DOCKER_GROUP_DATABASE = 'database';

    /**
     * Define the docker default tag.
     */
    protected const DOCKER_DEFAULT_TAG = 'latest';

    /**
     * Define the docker registry repository URL.
     */
    protected const DOCKER_REGISTRY_REPOSITORY_URL = 'https://registry.hub.docker.com/v1/repositories/';

    /**
     * Docker service configurations.
     *
     * @var array
     */
    protected $configuration;

    /**
     * The docker service constructor.
     *
     * @param array $configuration
     *   An array of the service configuration.
     */
    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * {@inheritDoc}
     */
    public static function group(): string
    {
        return static::DOCKER_GROUP_OTHER;
    }

    /**
     * {@inheritDoc}
     */
    public function packageName(): string
    {
        $image = static::image();
        return ($pos = strpos($image, '/'))
            ? substr($image, $pos + 1)
            : $image;
    }

    /**
     * {@inheritDoc}
     */
    public function definition(): array
    {
        $image = static::image();
        $config = $this->getConfiguration();

        return [
            'image' => "{$image}:{$config['version']}",
            'restart' => 'always',
            'container_name' => $this->packageName()
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function configurationQuestions(): array
    {
        $image = static::image();
        $configuration = $this->getConfiguration();

        return [
            'version' => (new ChoiceQuestion(
                $this->formatQuestion("Select {$image} release tag", $configuration['version']),
                $this->getDockerPackageTagOptions(),
                $configuration['version']
            )),
        ];
    }

    /**
     * Get the docker service configuration.
     *
     * @return array
     *   An array of the configuration.
     */
    protected function getConfiguration(): array
    {
        return array_replace(
            $this->defaultConfiguration(),
            $this->configuration,
        );
    }

    /**
     * Define the default configuration.
     *
     * @return array
     *   An array of the default configurations.
     */
    protected function defaultConfiguration(): array
    {
        return [
            'version' => static::DOCKER_DEFAULT_TAG
        ];
    }

    /**
     * Get the docker package tag options.
     *
     * @return array
     *   An array of package tags options.
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    protected function getDockerPackageTagOptions(): array
    {
        $options = [];

        foreach ($this->fetchDockerPackageTags() as $tag) {
            if (!isset($tag['name']) || strpos($tag['name'], '-') !== false) {
                continue;
            }
            $options[] = $tag['name'];
        }
        return $options;
    }

    /**
     * Fetch the docker package tags.
     *
     * @return array
     *   An array of the docker package tags.
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    protected function fetchDockerPackageTags(): array
    {
        $package = static::image();

        try {
            $request = $this->dockerHttpClient()->request('GET', "{$package}/tags");
            if ($request->getStatusCode() !== 200) {
                return [];
            }
            return $request->toArray();
        } catch (\Exception $exception) {
            throw new \RuntimeException(
                sprintf('Unable to load the image tags for %s package.', $package)
            );
        }
    }

    /**
     * Get the docker registry HTTP client.
     *
     * @return \Symfony\Contracts\HttpClient\HttpClientInterface
     *   The HTTP client instance.
     */
    protected function dockerHttpClient(): HttpClientInterface
    {
        /** @var \Symfony\Contracts\HttpClient\HttpClientInterface $client */
        $client = PxApp::service('httpClient');

        return $client->withOptions([
            'base_uri' => static::DOCKER_REGISTRY_REPOSITORY_URL,
        ]);
    }

    /**
     * Format the console question message.
     *
     * @param string $message
     *   The console question message.
     * @param string|int|null $default
     *   The question default value.
     *
     * @return string
     *   The formatted question message.
     */
    protected function formatQuestion(string $message, $default = null): string
    {
        if (isset($default)) {
            $message .= " [{$default}]";
        }

        return "<question>?  $message </question>";
    }
}
