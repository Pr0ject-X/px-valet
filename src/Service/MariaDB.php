<?php

declare(strict_types=1);

namespace Pr0jectX\PxValet\Service;

/**
 * Define the MariaDB docker service.
 */
class MariaDB extends MySQL
{
    /**
     * {@inheritDoc}
     */
    public static function label(): string
    {
        return 'MariaDB';
    }

    /**
     * {@inheritDoc}
     */
    public static function image(): string
    {
        return 'mariadb';
    }

    /**
     * {@inheritDoc}
     */
    protected function environment(): array
    {
        $config = $this->getConfiguration();

        return [
            'MARIADB_USER' => $config['username'],
            'MARIADB_PASSWORD' => $config['password'],
            'MARIADB_DATABASE' => $config['database'],
            'MARIADB_ROOT_PASSWORD' => 'root',
        ];
    }
}
