<?php

declare(strict_types=1);

namespace Pr0jectX\PxValet\ProjectX\Plugin\EnvironmentType\Commands;

use Pr0jectX\Px\Contracts\DatabaseCommandInterface;
use Pr0jectX\Px\ExecutableBuilder\Commands\MySql;
use Pr0jectX\Px\ExecutableBuilder\Commands\MySqlDump;
use Pr0jectX\Px\ProjectX\Plugin\EnvironmentType\EnvironmentDatabase;
use Pr0jectX\Px\ProjectX\Plugin\EnvironmentType\EnvironmentTypeInterface;
use Pr0jectX\Px\ProjectX\Plugin\PluginCommandTaskBase;
use Pr0jectX\Px\PxApp;
use Pr0jectX\PxValet\Valet;
use Robo\Collection\CollectionBuilder;

/**
 * Define the environment database commands.
 */
class DatabaseCommands extends PluginCommandTaskBase implements DatabaseCommandInterface
{
    /**
     * Define default database application.
     */
    protected const DEFAULT_DB_APPLICATION = 'sequel_ace';

    /**
     * Connect to the environment database using an external application.
     *
     * @param string|null $appName
     *   The DB application name e.g (sequel_pro, sequel_ace).
     */
    public function dbLaunch(string $appName = null): void
    {
        try {
            $appOptions = $this->getDatabaseApplicationOptions();

            if (empty($appOptions)) {
                throw new \RuntimeException(
                    'There are no supported database applications found!'
                );
            }

            if (!isset($appName)) {
                $appDefault = array_key_exists(static::DEFAULT_DB_APPLICATION, $appOptions)
                    ? static::DEFAULT_DB_APPLICATION
                    : array_key_first($appOptions);

                $appName = count($appOptions) === 1
                    ? array_key_first($appOptions)
                    : $this->askChoice(
                        'Select the database application to launch',
                        $appOptions,
                        $appDefault
                    );
            }

            if (!isset($this->databaseApplicationDefinitions()[$appName])) {
                throw new \InvalidArgumentException(sprintf(
                    'The database application %s is invalid!',
                    $appName
                ));
            }
            $this->executeDatabaseApplication($appName);
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * Import the primary database into the environment.
     *
     * @param string $importFile
     *   The database import file.
     * @param array $opts
     *   The database import options.
     * @oprion string $host
     *   Set the database host (defaults to database type if negated).
     * @option string $database
     *   Set the database table (defaults to database type if negated).
     * @option string $username
     *   Set the database username (defaults to database type if negated).
     * @option string $password
     *   Set the database password (defaults to database type if negated).
     * @option string $type
     *   Set the database type based on the environment, (e.g. primary and secondary).
     */
    public function dbImport(string $importFile, array $opts = [
        'host' => null,
        'database' => null,
        'username' => null,
        'password' => null,
        'type' => 'primary',
    ]): void
    {
        try {
            if (!file_exists($importFile)) {
                throw new \InvalidArgumentException(
                    'The database import file does not exist.'
                );
            }
            $database = $this->createDatabase($opts);

            if (!isset($database)) {
                throw new \InvalidArgumentException(
                    'Invalid database configuration have been provided.'
                );
            }

            $this->importDatabase(
                $database->getHost(),
                $database->getDatabase(),
                $database->getUsername(),
                $database->getPassword(),
                $importFile,
            );
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * Export the primary database from the environment.
     *
     * @param string $exportDir
     *   The local export directory.
     * @param array $opts
     *   The database export options.
     *
     * @oprion string $host
     *   Set the database host (defaults to database type if negated).
     * @option string $database
     *   Set the database table (defaults to database type if negated).
     * @option string $username
     *   Set the database username (defaults to database type if negated).
     * @option string $password
     *   Set the database password (defaults to database type if negated).
     * @option string $filename
     *   The database export filename.
     * @option string $type
     *   Set the database type based on the environment, (e.g. primary and secondary).
     */
    public function dbExport(string $exportDir, array $opts = [
        'host' => null,
        'database' => null,
        'username' => null,
        'password' => null,
        'filename' => 'db',
        'type' => 'primary',
    ]): void
    {
        try {
            if (!is_dir($exportDir)) {
                throw new \InvalidArgumentException(
                    'The database export directory does not exist.'
                );
            }
            $database = $this->createDatabase($opts);

            if (!isset($database)) {
                throw new \InvalidArgumentException(
                    'Invalid database configuration have been provided.'
                );
            }
            $exportFile = "{$exportDir}/{$opts['filename']}";

            $this->exportDatabase(
                $database->getHost(),
                $database->getDatabase(),
                $database->getUsername(),
                $database->getPassword(),
                $exportFile
            );
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * The database application definitions.
     *
     * @return array[]
     *   An array of the database application definitions.
     */
    protected function databaseApplicationDefinitions(): array
    {
        return [
            'sequel_ace' => [
                'os' => 'Darwin',
                'label' => 'Sequel Ace',
                'locations' => '/Applications/Sequel Ace.app',
                'execute' => function (string $appLocation) {
                    $this->openSequelApplication($appLocation);
                }
            ],
            'sequel_pro' => [
                'os' => 'Darwin',
                'label' => 'Sequel Pro',
                'locations' => '/Applications/Sequel Pro.app',
                'execute' => function (string $appLocation) {
                    $this->openSequelApplication($appLocation);
                }
            ],
            'table_plus' => [
                'os' => 'Darwin',
                'label' => 'TablePlus',
                'locations' => [
                    '/Applications/TablePlus.app',
                    '/Applications/Setapp/TablePlus.app',
                ],
                'execute' => function (string $appLocation) {
                    $this->openTablePlusApplication($appLocation);
                }
            ],
        ];
    }

    /**
     * Get the database application options.
     *
     * @return array
     *   An array of the database application options.
     */
    protected function getDatabaseApplicationOptions(): array
    {
        $options = [];

        foreach ($this->findActiveDatabaseApplications() as $key => $info) {
            if (!isset($info['label'])) {
                continue;
            }
            $options[$key] = $info['label'];
        }

        return $options;
    }

    /**
     * Get an active database application definition.
     *
     * @param string $name
     *   The database application machine name.
     *
     * @return array
     *   An array of the database application definition parameters.
     */
    protected function getDatabaseApplicationDefinition(string $name): array
    {
        return $this->findActiveDatabaseApplications()[$name] ?? [];
    }

    /**
     * Execute the database application.
     *
     * @param string $name
     *   The database application machine name.
     */
    protected function executeDatabaseApplication(string $name): void
    {
        $dbInfo = $this->getDatabaseApplicationDefinition($name);

        if (is_callable($dbInfo['execute'])) {
            call_user_func($dbInfo['execute'], $dbInfo['location']);
        }
    }

    /**
     * Find active database applications.
     *
     * @return array
     *   An array of database applications found on the host.
     */
    protected function findActiveDatabaseApplications(): array
    {
        $dbApps = [];

        foreach ($this->databaseApplicationDefinitions() as $appKey => $appInfo) {
            if ($appInfo['os'] === PHP_OS && isset($appInfo['locations'])) {
                $locations = $appInfo['locations'];

                if (!is_array($locations)) {
                    $locations = [$locations];
                }
                $location = $this->getDatabaseApplicationLocation($locations);

                if (!isset($location)) {
                    continue;
                }

                $dbApps[$appKey] = [
                    'label' => $appInfo['label'],
                    'execute' => $appInfo['execute'],
                    'location' => $location
                ];
            }
        }

        return $dbApps;
    }

    /**
     * Get the database application location.
     *
     * @param array $locations
     *   An array of searchable locations.
     *
     * @return string|null
     *   The database application location on the host file system.
     */
    protected function getDatabaseApplicationLocation(array $locations): ?string
    {
        foreach ($locations as $location) {
            if (!file_exists($location)) {
                continue;
            }
            return $location;
        }

        return null;
    }

    /**
     * Define the TablePlus database URL.
     *
     * @return string|null
     *   The TablePlus database URL.
     */
    protected function tablePlusDatabaseUrl(): ?string
    {
        $database = $this->primaryEnvironmentDatabase();

        if (!$database->isValid()) {
            return null;
        }

        return "{$database->getType()}://{$database->getUsername()}:{$database->getPassword()}@{$database->getHost()}/{$database->getDatabase()}";
    }

    /**
     * Open TablePlus application.
     *
     * @param string $appPath
     *   The database application location path.
     */
    protected function openTablePlusApplication(string $appPath): void
    {
        if ($url = $this->tablePlusDatabaseUrl()) {
            $query = http_build_query([
                'statusColor' => '007F3D',
                'enviroment' => 'local',
                'name' => 'Project-X Local Database',
                'tLSMode' => 0,
                'usePrivateKey' => 'true',
                'safeModeLevel' => 0,
                'advancedSafeModeLevel' => 0
            ]);
            $this->taskExec("open '{$url}?{$query}' -a '{$appPath}'")->run();
        }
    }

    /**
     * Prepare sequel database file task.
     *
     * @param string $file
     *   The project sequel file.
     * @param \Pr0jectX\Px\ProjectX\Plugin\EnvironmentType\EnvironmentDatabase $database
     *   The environment database.
     *
     * @return \Robo\Collection\CollectionBuilder
     *   The collection builder.
     */
    protected function prepareSequelDatabaseFileTask(
        string $file,
        EnvironmentDatabase $database
    ): CollectionBuilder {
        return $this->taskWriteToFile($file)
            ->text($this->sequelTemplateBase())
            ->place('label', 'Project-X Database')
            ->place('host', $database->getHost())
            ->place('database', $database->getDatabase())
            ->place('username', $database->getUsername())
            ->place('password', $database->getPassword())
            ->place('port', $database->getPort());
    }

    /**
     * Get the sequel template base.
     *
     * @return null|string
     *   The sequel template file contents.
     */
    protected function sequelTemplateBase(): ?string
    {
        return Valet::loadTemplateFile('sequel.xml');
    }

    /**
     * Open the sequel (pro/ace) application.
     *
     * @param string $appPath
     *   The database application location path.
     */
    protected function openSequelApplication(string $appPath): void
    {
        $database = $this->primaryEnvironmentDatabase();

        if ($database->isValid()) {
            $projectTempDir = PxApp::projectTempDir();
            $sequelFile = "{$projectTempDir}/sequel.spf";

            $writeResponse = $this->prepareSequelDatabaseFileTask(
                $sequelFile,
                $database
            )->run();

            if ($writeResponse->wasSuccessful()) {
                $this->taskExec("open -a '{$appPath}' {$sequelFile}")->run();
            }
        }
    }

    /**
     * Run the import database process.
     *
     * @param string $host
     *   The database host.
     * @param string|null $database
     *   The database name.
     * @param string|null $username
     *   The database user.
     * @param string|null $password
     *   The database user password.
     * @param string $importFile
     *   The database import file path.
     */
    protected function importDatabase(
        string $host,
        string $database,
        string $username,
        string $password,
        string $importFile
    ): void {
        if (!$this->hasDatabaseConnection($host, $username, $password)) {
            throw new \InvalidArgumentException(
                'Invalid database connection.'
            );
        }

        if ($command = $this->application()->find('env:execute')) {
            $mysqlCommand = (new MySql())
                ->host($host)
                ->user($username)
                ->password($password)
                ->database($database)
                ->build();

            $importCommand = !$this->isGzipped($importFile)
                ? "{$mysqlCommand} < {$importFile}"
                : "gzcat {$importFile} | {$mysqlCommand}";

            $result = $this->taskSymfonyCommand($command)
                ->arg('cmd', $importCommand)
                ->run();

            if ($result->wasSuccessful()) {
                $this->success(
                    'The database was successfully imported!'
                );
            }
        }
    }

    /**
     * Run the export database process.
     *
     * @param string $host
     *   The database host.
     * @param string|null $database
     *   The database name.
     * @param string|null $username
     *   The database user.
     * @param string|null $password
     *   The database user password.
     * @param string $exportFile
     *   The database export filename.
     */
    protected function exportDatabase(
        string $host,
        string $database,
        string $username,
        string $password,
        string $exportFile
    ): void {
        if (!$this->hasDatabaseConnection($host, $username, $password)) {
            throw new \InvalidArgumentException(
                'Invalid database connection.'
            );
        }

        if ($command = $this->application()->find('env:execute')) {
            $mysqlDump = (new MySqlDump())
                ->host($host)
                ->user($username)
                ->password($password)
                ->database($database)
                ->noTablespaces()
                ->build();

            $dbFilename = "{$exportFile}.sql.gz";
            $result = $this->taskSymfonyCommand($command)
                ->arg('cmd', "{$mysqlDump} | gzip -c > {$dbFilename}")->run();

            if ($result->wasSuccessful()) {
                $this->success(
                    'The database was successfully exported!'
                );
            }
        }
    }

    /**
     * Has database connection.
     *
     * @param string $host
     *   The database host.
     * @param string $username
     *   The database username.
     * @param string $password
     *   The database password.
     *
     * @return bool
     *   Return true if database connection is valid; otherwise false.
     */
    protected function hasDatabaseConnection(
        string $host,
        string $username,
        string $password
    ): bool {
        try {
            (new \PDO("mysql:host={$host}", $username, $password))->setAttribute(
                \PDO::ATTR_ERRMODE,
                \PDO::ERRMODE_EXCEPTION
            );
        } catch (\Exception $exception) {
            return false;
        }

        return true;
    }

    /**
     * Determine if the file is gzipped.
     *
     * @param string $filepath
     *   The fully qualified path to the file.
     *
     * @return bool
     */
    protected function isGzipped(string $filepath): bool
    {
        if (!file_exists($filepath)) {
            throw new \InvalidArgumentException(
                'The file path does not exist.'
            );
        }
        $contentType = mime_content_type($filepath);

        $mimeType = substr(
            $contentType,
            strpos($contentType, '/') + 1
        );

        return $mimeType === 'x-gzip' || $mimeType === 'gzip';
    }

    /**
     * Create a environment database instance.
     *
     * @param array $config
     *   An array of database configuration.
     *
     * @return \Pr0jectX\Px\ProjectX\Plugin\EnvironmentType\EnvironmentDatabase|null
     */
    protected function createDatabase(array $config): ?EnvironmentDatabase
    {
        if (isset($config['host'], $config['database'], $config['username'], $config['password'])) {
            return (new EnvironmentDatabase())
                ->setType('mysql')
                ->setHost($config['host'])
                ->setDatabase($config['database'])
                ->setUsername($config['username'])
                ->setPassword($config['password']);
        }

        $allowedTypes = [
            EnvironmentTypeInterface::ENVIRONMENT_DB_PRIMARY,
            EnvironmentTypeInterface::ENVIRONMENT_DB_SECONDARY,
        ];

        if (isset($config['type']) && in_array($config['type'], $allowedTypes, true)) {
            return $this->environmentInstance()->selectEnvDatabase($config['type']);
        }

        return null;
    }

    /**
     * The primary environment database.
     *
     * @return \Pr0jectX\Px\ProjectX\Plugin\EnvironmentType\EnvironmentDatabase
     */
    protected function primaryEnvironmentDatabase(): EnvironmentDatabase
    {
        return $this->environmentInstance()->selectEnvDatabase(
            EnvironmentTypeInterface::ENVIRONMENT_DB_PRIMARY
        );
    }

    /**
     * The current environment type instance.
     *
     * @return \Pr0jectX\Px\ProjectX\Plugin\EnvironmentType\EnvironmentTypeInterface
     */
    protected function environmentInstance(): EnvironmentTypeInterface
    {
        return PxApp::getEnvironmentInstance();
    }
}
