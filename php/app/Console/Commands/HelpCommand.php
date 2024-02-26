<?php

declare(strict_types=1);
namespace App\Console\Commands;

use App\Console\CommandRouter;

class HelpCommand extends AbstractCommand
{
    /**
     * @return int|null
     * @throws \Exception
     */
    public function __invoke(): ?int
    {
        echo "Usage:\n\tphp console.php <action> [<arguments...>]\n\n";
        echo "Available commands:\n\n";

        $commands = [];
        $maxCommandLength = 0;
        foreach (CommandRouter::$commands as $commandClass => $commandDescription) {
            $commandName = CommandRouter::classToCommand($commandClass);
            $commands[] = [
                'name' => $commandName,
                'description' => $commandDescription,
            ];
            if (($commandNameLength = strlen($commandName)) > $maxCommandLength) {
                $maxCommandLength = $commandNameLength;
            }
        }

        foreach ($commands as $command) {
            echo "\t" . str_pad($command['name'], $maxCommandLength, ' ') . "   {$command['description']}\n";
        }

        return 255;
    }

}
