<?php

declare(strict_types=1);

namespace Pr0jectX\PxValet\ExecutableBuilder\Commands;

use Pr0jectX\Px\ExecutableBuilder\ExecutableBuilderBase;

/**
 * Define the sub command executable builder base class.
 */
abstract class SubCommandExecutable extends ExecutableBuilderBase
{
    /**
     * @var string
     */
    protected $subCommand;

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
