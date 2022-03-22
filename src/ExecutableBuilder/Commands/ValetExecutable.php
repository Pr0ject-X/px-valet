<?php

declare(strict_types=1);

namespace Pr0jectX\PxValet\ExecutableBuilder\Commands;

/**
 * Define the valet executable instance.
 */
class ValetExecutable extends SubCommandExecutable
{
    /**
     * {@inheritdoc}
     */
    protected const EXECUTABLE = 'valet';

    /**
     * Invoke the Valet install.
     *
     * @return $this
     */
    public function install(): self
    {
        $this->setSubCommand(__FUNCTION__);

        return $this;
    }

    /**
     * Invoke the Valet start.
     *
     * @return $this
     */
    public function start(): self
    {
        $this->setSubCommand(__FUNCTION__);

        return $this;
    }

    /**
     * Invoke the Valet stop.
     *
     * @return $this
     */
    public function stop(): self
    {
        $this->setSubCommand(__FUNCTION__);

        return $this;
    }

    /**
     * Invoke the Valet link.
     *
     * @param string $name
     *   The project domain name.
     *
     * @return $this
     */
    public function link(string $name): self
    {
        $this->setSubCommand(__FUNCTION__)->setArgument($name);

        return $this;
    }

    /**
     * Invoke the Valet unlink.
     *
     * @param string $name
     *   The project domain name.
     *
     * @return $this
     */
    public function unlink(string $name): self
    {
        $this->setSubCommand(__FUNCTION__)->setArgument($name);

        return $this;
    }

    /**
     * Invoke the Valet secure.
     *
     * @param string $name
     *   The project domain name.
     *
     * @return $this
     */
    public function secure(string $name): self
    {
        $this->setSubCommand(__FUNCTION__)->setArgument($name);

        return $this;
    }

    /**
     * Invoke the Valet unsecure.
     *
     * @param string $name
     *   The project domain name.
     *
     * @return $this
     */
    public function unsecure(string $name): self
    {
        $this->setSubCommand(__FUNCTION__)->setArgument($name);

        return $this;
    }

    /**
     * Invoke the Valet restart.
     *
     * @param string|null $service
     *   The Valet service name.
     *
     * @return $this
     */
    public function restart(?string $service = null): self
    {
        $command = $this->setSubCommand(__FUNCTION__);

        if (isset($service) && in_array($service, ['dnsmasq', 'nginx', 'php'])) {
            $command->setArgument($service);
        }

        return $this;
    }
}
