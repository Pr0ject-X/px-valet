<?php

declare(strict_types=1);

namespace Pr0jectX\PxValet;

use Pr0jectX\Px\Datastore\YamlDatastore;
use Pr0jectX\PxValet\Contracts\DockerServiceInterface;

/**
 * Define docker compose builder.
 */
class DockerComposeBuilder
{
    /**
     * Define the docker compose filename.
     */
    public const DOCKER_COMPOSE_FILENAME = 'docker-compose.yml';

    /**
     * The docker compose output.
     *
     * @var array
     *   An array of the docker compose structure.
     */
    protected $output = [];

    /**
     * @var \Pr0jectX\Px\Datastore\YamlDatastore
     */
    protected $datastore;

    /**
     * Docker compose builder constructor.
     *
     * @param string $filepath
     *   The filepath to the docker composer yml file.
     */
    public function __construct(string $filepath)
    {
        $filename = static::DOCKER_COMPOSE_FILENAME;

        $this->datastore = new YamlDatastore(
            "{$filepath}/{$filename}"
        );
    }

    /**
     * Set the docker compose version.
     *
     * @param float $version
     *   The docker compose version.
     *
     * @return self
     */
    public function setVersion(float $version): self
    {
        $this->output['version'] = (string) $version;

        return $this;
    }

    /**
     * Set docker compose services.
     *
     * @param string $key
     *   The docker service key.
     * @param \Pr0jectX\PxValet\Contracts\DockerServiceInterface $service
     *   The docker service instance.
     *
     * @return self
     */
    public function setService(string $key, DockerServiceInterface $service): self
    {
        if ($definition = $service->definition()) {
            $this->output['services'][$key] = $definition;

            if (isset($definition['volumes'])) {
                foreach ($definition['volumes'] as $volume) {
                    $data = explode(':', $volume);
                    if (($namedVolume = array_shift($data)) && strpos($namedVolume, '/') == false) {
                        $this->setVolumes($namedVolume, [
                            'driver' => 'local'
                        ]);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Set the docker compose volumes.
     *
     * @param string $name
     *   The docker compose volume name.
     * @param array $config
     *   The docker compose volume configuration.
     *
     * @return $this
     */
    public function setVolumes(string $name, array $config): self
    {
        if (!isset($this->output['volumes'][$name])) {
            $this->output['volumes'][$name] = $config;
        }

        return $this;
    }

    /**
     * Save the docker compose file.
     *
     * @return bool
     *   Return true if successfully saved; otherwise false.
     */
    public function save(): bool
    {
        return $this->datastore
            ->setInline(4)
            ->write($this->output());
    }

    /**
     * Get the docker compose output.
     *
     * @return array
     *   An array of the docker compose output.
     */
    protected function output(): array
    {
        return $this->output;
    }
}
