<?php

declare(strict_types=1);

namespace App\Utils;

final class Console
{
    /**
     * Confirmation dialog with question where user must enter Yes or No. Has default option which is used on empty
     * user input.
     *
     * @param string $question
     * @param bool $default
     * @return bool
     */
    public static function confirm(string $question, bool $default = false): bool
    {
        do {
            $input = self::ask("{$question} (y/n)", ($default ? 'yes' : 'no'));
            switch (strtolower($input)) {
                case '':
                    return $default;
                case 'y':
                case 'yes':
                    return true;
                case 'n':
                case 'no':
                    return false;
                default:
                    echo "Unexpected input \"{$input}\"\n";
            }
        } while(true);
        return false; // This return will never be reached, but PhpStorm warns about missing return statement otherwise
    }

    /**
     * Ask question from user. User can enter any input. Has default options which is used on empty user input.
     *
     * @param string $question
     * @param string|null $default
     * @return string
     */
    public static function ask(string $question, string $default = null): string
    {
        return readline("{$question} " . ($default !== null ? "[$default] " : ''));
    }

    /**
     * The same as ask() but with storing entered input in history so user can reuse previous input by pressing
     * arrow up key.
     *
     * @param string $question
     * @param string|null $default
     * @return string
     */
    public static function askWithHistory(string $question, string $default = null): string
    {
        $input = self::ask($question, $default);
        readline_add_history($input);
        return $input;
    }
}
