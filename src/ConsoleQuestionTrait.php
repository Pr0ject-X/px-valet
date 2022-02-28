<?php

declare(strict_types=1);

namespace Pr0jectX\PxValet;

use Symfony\Component\Console\Question\Question;

/**
 * Define the console question trait.
 */
trait ConsoleQuestionTrait
{
    /**
     * Set a required question.
     *
     * @param \Symfony\Component\Console\Question\Question $question
     *   Input the question object.
     * @param string $message
     *   Input the message to use for the exception.
     * @param callable|null $normalizeCallback
     *   The question normalize callback.
     *
     * @return \Symfony\Component\Console\Question\Question
     */
    protected function setRequiredQuestion(
        Question $question,
        string $message = 'This field is required!',
        ?callable $normalizeCallback = null
    ): Question {
        $question = ($question)->setValidator(function ($value) use ($message) {
            if (!isset($value)) {
                throw new \RuntimeException(
                    $message
                );
            }
            return $value;
        });

        if (is_callable($normalizeCallback)) {
            $question->setNormalizer($normalizeCallback);
        }

        return $question;
    }
}
