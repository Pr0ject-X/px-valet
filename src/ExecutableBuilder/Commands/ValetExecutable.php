<?php

declare(strict_types=1);

namespace Pr0jectX\PxValet\ExecutableBuilder\Commands;

use Pr0jectX\Px\ExecutableBuilder\ExecutableBuilderBase;

/**
 * Define the valet executable instance.
 */
class ValetExecutable extends ExecutableBuilderBase
{
    /**
     * {@inheritdoc}
     */
    protected const EXECUTABLE = 'valet';

    /**
     * @var string
     */
    protected $subCommand;

    /**
     * Set the valet install.
     *
     * @return $this
     */
    public function install(): self
    {
        $this->setSubCommand(__FUNCTION__);

        return $this;
    }

    /**
     * Set the valet link.
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
     * Set the valet unlink.
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
     * Set the valet secure.
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
     * Set the valet unsecure.
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
     * Set the command sub-command.
     *
     * @param string $subCommand
     *   The executable sub command.
     *
     * @return $this
     */
    protected function setSubCommand(string $subCommand): self
    {
        $this->subCommand = $subCommand;

        return $this;
    }

    /**
     * Define the command structure.
     *
     * @return array
     */
    protected function executableStructure(): array
    {
        return [
            static::EXECUTABLE,
            $this->subCommand,
            $this->flattenOptions(),
            $this->flattenArguments()
        ];
    }
}
