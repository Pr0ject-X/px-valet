<?php

declare(strict_types=1);

namespace Pr0jectX\PxValet\ExecutableBuilder\Commands;

/**
 * Define the Pecl executable.
 */
class Pecl extends SubCommandExecutable
{
    /**
     * @inheritdoc
     */
    protected const EXECUTABLE = 'pecl';

    /**
     * The Pecl install command.
     *
     * @param string|array $package
     *
     * @return $this
     */
    public function install($package): self
    {
        if (is_array($package)) {
            $package = implode(' ', $package);
        }

        $this->setSubCommand(__FUNCTION__)->setArgument($package);

        return $this;
    }
}
