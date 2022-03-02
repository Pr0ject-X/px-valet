<?php

declare(strict_types=1);

namespace Pr0jectX\PxValet\ProjectX\Plugin\EnvironmentType;

use Droath\RoboDockerCompose\Task\loadTasks as DockerComposeTasks;
use Pr0jectX\Px\ConfigTreeBuilder\ConfigTreeBuilder;
use Pr0jectX\Px\ProjectX\Plugin\EnvironmentType\EnvironmentDatabase;
use Pr0jectX\Px\ProjectX\Plugin\PluginConfigurationBuilderInterface;
use Pr0jectX\Px\PxApp;
use Pr0jectX\PxValet\ConsoleQuestionTrait;
use Pr0jectX\PxValet\Contracts\DockerServiceInterface;
use Pr0jectX\PxValet\DockerComposeBuilder;
use Pr0jectX\PxValet\DockerServiceBase;
use Pr0jectX\PxValet\ExecutableBuilder\Commands\ValetExecutable;
use Pr0jectX\PxValet\ProjectX\Plugin\EnvironmentType\Commands\DatabaseCommands;
use Pr0jectX\PxValet\Valet;
use Pr0jectX\Px\ProjectX\Plugin\EnvironmentType\EnvironmentTypeBase;
use Pr0jectX\PxValet\DockerServiceManager;
use Robo\Result;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Define the valet environment type plugin.
 */
class ValetEnvironmentType extends EnvironmentTypeBase implements PluginConfigurationBuilderInterface
{
    use DockerComposeTasks;
    use ConsoleQuestionTrait;

    /**
     * Valet configuration directory path.
     *
     * @var string|null
     *   The value configuration directory path.
     */
    protected $configDirectory;

    /**
     * {@inheritDoc}
     */
    public static function pluginId(): string
    {
        return 'valet';
    }

    /**
     * {@inheritDoc}
     */
    public static function pluginLabel(): string
    {
        return 'Valet';
    }

    /**
     * Print the plugin banner.
     */
    public function printBanner(): self
    {
        print Valet::pluginBanner();

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function registeredCommands(): array
    {
        return array_merge([
            DatabaseCommands::class
        ], parent::registeredCommands());
    }

    /**
     * {@inheritDoc}
     */
    public function init(array $opts = []): void
    {
        $this
            ->printBanner()
            ->runInstallation()
            ->writeDockerCompose();
    }

    /**
     * {@inheritDoc}
     */
    public function start(array $opts = []): void
    {
        try {
            /** @var \Droath\RoboDockerCompose\Task\Up $task */
            $task = $this->taskDockerComposeUp();

            $status = $task->file($this->valetDockerComposeFilePath())
                ->detachedMode()
                ->run();

            if ($status->wasSuccessful()) {
                $this->success('The docker services are running!');
            }
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function stop(array $opts = []): void
    {
        try {
            /** @var \Droath\RoboDockerCompose\Task\Up $task */
            $task = $this->taskDockerComposeDown();
            $status = $task->file($this->valetDockerComposeFilePath())->run();

            if ($status->wasSuccessful()) {
                $this->success(
                    'The docker services have been stopped!'
                );
            }

            if ($this->confirm('Stop local Valet services? [no]')) {
                $this->taskExec(
                    (new ValetExecutable())->setArgument('stop')->build()
                )->run();
            }
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function restart(array $opts = []): void
    {
        try {
            $restartResult = $this->taskExec(
                (new ValetExecutable())->setArgument('restart')->build()
            )->run();

            if ($restartResult->wasSuccessful()) {
                /** @var \Droath\RoboDockerCompose\Task\Restart $task */
                $task = $this->taskDockerComposeRestart();
                $status = $task->file($this->valetDockerComposeFilePath())->run();

                if ($status->wasSuccessful()) {
                    $this->success(
                        'The host services have successfully restarted!'
                    );
                }
            }
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function destroy(array $opts = []): void
    {
        try {
            $stack = $this->taskExecStack();

            foreach ($this->getConfigurations()['domains'] as $domain) {
                if (!isset($domain['name'])) {
                    continue;
                }
                $name = $domain['name'];

                if ($this->valetSiteExists($name)) {
                    $stack->exec(
                        (new ValetExecutable())->unlink($name)->build()
                    );

                    if ($this->valetDomainCertExists($name)) {
                        $stack->exec(
                            (new ValetExecutable())->unsecure($name)->build()
                        );
                    }
                }
            }
            $status = $stack->run();

            if ($status->wasSuccessful()) {
                $this->success(
                    'The valet domain/certs for this project were successfully removed!'
                );
            }
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function launch(array $opts = []): void
    {
        $domains = $this->getConfigDomains();

        $hostname = count($domains) > 1
            ? $this->askChoice('Select the host', array_keys($domains))
            : key($domains);

        $this->taskOpenBrowser($domains[$hostname])->run();
    }

    /**
     * {@inheritDoc}
     */
    public function exec(string $cmd, array $opts = []): ?Result
    {
        try {
            return $this->taskExec($cmd)->run();
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function envPackages(): array
    {
        return [
            'drush',
            'composer',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function envAppRoot(): string
    {
        return PxApp::projectRootPath() . "/{$this->getConfigurations()['app_root']}";
    }

    /**
     * {@inheritDoc}
     */
    public function envDatabases(): array
    {
        $databases = [];
        $services = $this->getConfigurationGroupedByImages(
            DockerServiceBase::DOCKER_GROUP_DATABASE
        );

        foreach ($services as $type => $config) {
            $database = (new EnvironmentDatabase())
                ->setType($type)
                ->setPort($config['port'])
                ->setHost('127.0.0.1')
                ->setDatabase($config['database'])
                ->setUsername($config['username'])
                ->setPassword($config['password']);
            $databases[static::ENVIRONMENT_DB_PRIMARY] = $database;
        }

        return $databases;
    }

    /**
     * {@inheritDoc}
     */
    public function pluginConfiguration(): ConfigTreeBuilder
    {
        $configs = $this->getConfigurations();
        $configBuilder = $this->configTreeInstance();

        $configBuilder->createNode('app_root')
            ->setValue($this->setRequiredQuestion(
                new Question(
                    $this->formatQuestionDefault('Input the application root', $configs['app_root']),
                    $configs['app_root']
                ),
                'The application root is required!'
            ))
        ->end();

        $configBuilder->createNode('domains')->setValue(function () {
            $index = 0;
            $domains = $this->getConfigurations()['domains'];
            $domainCount = count($domains);

            do {
                $defaultDomain = $domains[$index]['name'] ?? null;

                if (!isset($defaultDomain) && $index === 0) {
                    $defaultDomain = basename(PxApp::projectRootPath());
                }

                $value[$index] = [
                    'name' => $this->doAsk($this->setRequiredQuestion(
                        new Question(
                            $this->formatQuestionDefault('Input the domain name', $defaultDomain),
                            $defaultDomain
                        ),
                        'This field is required!',
                        function ($value) {
                            if ($pos = strpos($value, '.')) {
                                $value = substr($value, 0, $pos);
                            }
                            return strtolower(str_replace(' ', '-', $value));
                        }
                    )),
                    'ssl' => $this->confirm('Enable SSL for domain?', $domains[$index]['ssl'] ?? false),
                ];
                ++$index;
            } while ($index < $domainCount || $this->confirm('Add another domain?'));

            return $value;
        });

        $services = $configBuilder->createNode('services')->setArray(true);

        foreach ($this->dockerServiceGroups() as $group => $info) {
            $services->setKeyValue(
                $group,
                $this->dockerServiceQuestionRunner($group, $info['required'] ?? false)
            );
        }
        $services->end();

        return $configBuilder;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigurations(): array
    {
        return array_replace(
            $this->defaultConfigurations(),
            parent::getConfigurations(),
        );
    }

    /**
     * Run the valet installation.
     *
     * @return self
     */
    protected function runInstallation(): self
    {
        try {
            $stack = $this->taskExecStack();

            if (
                !$this->valetConfigDirExists()
                || $this->confirm('Valet is installed already, reinstall?')
            ) {
                $stack->exec(
                    (new ValetExecutable())->install()->build()
                );
            }

            if ($domains = $this->getConfigurations()['domains']) {
                $stack->exec("cd {$this->envAppRoot()}");

                foreach ($domains as $domain) {
                    if (!isset($domain['name'])) {
                        continue;
                    }
                    $name = $domain['name'];
                    $hasCert = $this->valetDomainCertExists($name);
                    $enableSSL = $domain['ssl'] ?? false;

                    if ($this->valetSiteExists($name)) {
                        if ($enableSSL && !$hasCert) {
                            $stack->exec(
                                (new ValetExecutable())->secure($name)->build()
                            );
                        } elseif (!$enableSSL && $hasCert) {
                            $stack->exec(
                                (new ValetExecutable())->unsecure($name)->build()
                            );
                        }
                    } else {
                        $stack->exec(
                            (new ValetExecutable())->link($name)->build()
                        );

                        if ($enableSSL && !$hasCert) {
                            $stack->exec(
                                (new ValetExecutable())->secure($name)->build()
                            );
                        }
                    }
                }
            }
            $stack->run();
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }

        return $this;
    }

    /**
     * Write the docker compose file.
     *
     * @return self
     */
    protected function writeDockerCompose(): self
    {
        $manager = $this->dockerServiceManager();
        $projectDockerDir = $this->valetDockerDirectory();

        $dockerCompose = (new DockerComposeBuilder($projectDockerDir))
            ->setVersion(3.1);

        foreach (array_keys($this->dockerServiceGroups()) as $group) {
            foreach ($this->getConfigurationGroupedByImages($group) as $image => $configuration) {
                if (empty($configuration)) {
                    continue;
                }
                $service = $manager->createInstance($image, $configuration);

                if (!$service instanceof DockerServiceInterface) {
                    continue;
                }
                $dockerCompose->setService(
                    $service->packageName(),
                    $service
                );

                if (
                    ($templateDir = $service->templateDirectory())
                    && file_exists($templateDir)
                ) {
                    $this->_mirrorDir(
                        $templateDir,
                        "{$projectDockerDir}/services/{$service->packageName()}"
                    );
                }
            }
        }

        if ($dockerCompose->save()) {
            $this->success('The docker compose file has successfully been saved!');
        }

        return $this;
    }

    /**
     * Define the docker service groups.
     *
     * @return array
     *   An array of docker service groups.
     */
    protected function dockerServiceGroups(): array
    {
        return [
            DockerServiceBase::DOCKER_GROUP_DATABASE => [
                'required' => true
            ],
            DockerServiceBase::DOCKER_GROUP_CACHING => [
                'required' => false
            ],
            DockerServiceBase::DOCKER_GROUP_OTHER => [
                'required' => false
            ],
        ];
    }

    /**
     * Get the configuration domains.
     *
     * @return array
     *   An array of domains keyed by hostname.
     */
    protected function getConfigDomains(): array
    {
        $domains = [];

        foreach ($this->getConfigurations()['domains'] as $domain) {
            if (!isset($domain['name'])) {
                continue;
            }
            $host = $domain['name'];
            $schema = $domain['ssl'] ? 'https' : 'http';
            $domains[$host] = "{$schema}://{$host}.test";
        }

        return $domains;
    }

    /**
     * Define the default configurations.
     *
     * @return array
     *   An array of default configurations.
     */
    protected function defaultConfigurations(): array
    {
        return [
            'app_root' => 'web',
            'domains' => [],
            'services' => []
        ];
    }

    /**
     * Retrieve the configurations grouped by service image.
     *
     * @param string $group
     *   The docker service group.
     *
     * @return array
     *   An array of configurations grouped by service image.
     */
    protected function getConfigurationGroupedByImages(
        string $group
    ): array {
        $info = [];

        if ($services = $this->getConfigurations()['services'][$group] ?? null) {
            foreach ($services as $service) {
                if (!isset($service['image'])) {
                    continue;
                }
                $info[$service['image']] = $service['configuration'];
            }
        }

        return $info;
    }

    /**
     * Docker service question runner.
     *
     * @param string $group
     *   The docker service group name.
     * @param bool $required
     *   Set to true to require a service.
     * @param bool $multiple
     *   Set to true to allow multiple services.
     * @return callable
     */
    protected function dockerServiceQuestionRunner(
        string $group,
        bool $required = false,
        bool $multiple = false
    ): callable {
        return function () use ($group, $multiple, $required) {
            $value = [];
            $manager = $this->dockerServiceManager();
            $options = $manager->serviceOptions($group);

            if (!empty($options)) {
                $images = $this->getConfigurationGroupedByImages($group);
                $serviceDefault = !empty($images) ? implode(',', array_keys($images)) : null;

                $confirm = $required || !empty($images) || $this->confirm(
                    "Add {$group} services"
                );

                if ($confirm) {
                    if (!isset($serviceDefault) && count($options) === 1) {
                        $serviceDefault = key($options);
                    }

                    $question = new ChoiceQuestion(
                        $this->formatQuestionDefault("Select the {$group} service", $serviceDefault),
                        $options,
                        $serviceDefault
                    );

                    if ($multiple) {
                        $question->setMultiselect(true);
                    }
                    $image = $this->doAsk($question);

                    if (!is_array($image)) {
                        $image = [$image];
                    }

                    foreach ($image as $index => $name) {
                        $value[$index]['image'] = $name;
                        $instance = $manager->createInstance($name, $images[$name] ?? []);

                        foreach ($instance->configurationQuestions() as $key => $question) {
                            if (!$question instanceof Question) {
                                continue;
                            }
                            $value[$index]['configuration'][$key] = $this->doAsk($question);
                        }
                    }
                }
            }

            return $value;
        };
    }

    /**
     * Define the valet configuration directories.
     *
     * @return string[]
     *   An array of configuration directories.
     */
    protected function valetConfigDirs(): array
    {
        $home = PxApp::userDir();

        return [
            "{$home}/.valet",
            "{$home}/.config/valet",
        ];
    }

    /**
     * Valet docker compose file path.
     */
    public function valetDockerComposeFilePath(): string
    {
        return implode(DIRECTORY_SEPARATOR, [
            $this->valetDockerDirectory(),
            DockerComposeBuilder::DOCKER_COMPOSE_FILENAME
        ]);
    }

    /**
     * Valet site exist in configuration.
     *
     * @param string $domain
     *   The valet site domain name.
     *
     * @return bool
     *   Return true if the valet site exists; otherwise false.
     */
    protected function valetSiteExists(string $domain): bool
    {
        return ($configDir = $this->findValetConfigDir())
            && file_exists("{$configDir}/Sites/{$domain}");
    }

    /**
     * Valet domain exist in configuration.
     *
     * @param string $domain
     *   The valet site domain name.
     * @param string $tld
     *   The valet site top level domain.
     *
     * @return bool
     *   Return true if the valet domain cert exists; otherwise false.
     */
    protected function valetDomainCertExists(
        string $domain,
        string $tld = 'test'
    ): bool {
        return ($configDir = $this->findValetConfigDir())
            && file_exists("{$configDir}/Certificates/{$domain}.{$tld}.crt");
    }

    /**
     * Valet configuration directory exists.
     *
     * @return bool
     *   Return true if directory exists; otherwise false.
     */
    protected function valetConfigDirExists(): bool
    {
        return !empty($this->findValetConfigDir());
    }

    /**
     * Valet project specific docker directory.
     *
     * @return string
     *   The docker directory path.
     */
    protected function valetDockerDirectory(): string
    {
        return implode(DIRECTORY_SEPARATOR, [PxApp::projectTempDir(), 'docker']);
    }

    /**
     * Find the valet configuration directory.
     *
     * @return string|null
     *   The current valet configuration directory.
     */
    protected function findValetConfigDir(): ?string
    {
        if (!isset($this->configDirectory)) {
            foreach ($this->valetConfigDirs() as $directory) {
                if (is_dir($directory) && file_exists($directory)) {
                    $this->configDirectory = $directory;
                    break;
                }
            }
        }

        return $this->configDirectory;
    }

    /**
     * The docker service manager.
     *
     * @return \Pr0jectX\PxValet\DockerServiceManager
     *   The docker service manager.
     */
    protected function dockerServiceManager(): DockerServiceManager
    {
        return new DockerServiceManager();
    }

    /**
     * The config tree builder instance.
     *
     * @return \Pr0jectX\Px\ConfigTreeBuilder\ConfigTreeBuilder
     *   The config tree builder instance.
     */
    protected function configTreeInstance(): ConfigTreeBuilder
    {
        return (new ConfigTreeBuilder())
            ->setQuestionInput($this->input())
            ->setQuestionOutput($this->output());
    }
}
