<?php

declare(strict_types=1);

namespace Pr0jectX\PxValet\Service;

use Pr0jectX\PxValet\DockerServiceBase;
use Symfony\Component\Console\Question\Question;

/**
 * Define the MailHog docker service.
 */
class MailHog extends DockerServiceBase
{
    /**
     * {@inheritDoc}
     */
    public static function label(): string
    {
        return 'MailHog';
    }

    /**
     * {@inheritDoc}
     */
    public static function image(): string
    {
        return 'mailhog/mailhog';
    }

    /**
     * {@inheritDoc}
     */
    public function configurationQuestions(): array
    {
        $config = $this->getConfiguration();
        $questions = parent::configurationQuestions();

        foreach (array_keys($this->ports()) as $key) {
            $name = str_replace('_', ' ', $key);
            $questions[$key] = $this->setRequiredQuestion(new Question(
                $this->formatQuestion("Input {$name}", $config[$key]),
                $config[$key]
            ));
        }

        return $questions;
    }

    /**
     * {@inheritDoc}
     */
    public function definition(): array
    {
        $config = $this->getConfiguration();

        return parent::definition() + [
            'ports' => [
                "{$config['web_port']}:8025",
                "{$config['smtp_port']}:1025"
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function defaultConfiguration(): array
    {
        return $this->ports() + parent::defaultConfiguration();
    }

    /**
     * Define the default ports.
     *
     * @return int[]
     */
    protected function ports(): array
    {
        return [
            'web_port' => 8025,
            'smtp_port' => 1025,
        ];
    }
}
