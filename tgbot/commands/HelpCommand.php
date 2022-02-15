<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;

class HelpCommand extends UserCommand
{
    protected $name = 'help';
    protected $description = '显示帮助指令';
    protected $usage = '/help 或 /help <指令>';
    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {
        $message     = $this->getMessage();
        $command_str = trim($message->getText(true));

        $safe_to_show = $message->getChat()->isPrivateChat();

        [$all_commands, $user_commands, $admin_commands] = $this->getUserAndAdminCommands();

        if ($command_str === '') {
            $text = '*指令列表*:' . PHP_EOL;
            foreach ($user_commands as $user_command) {
                $text .= '/' . $user_command->getName() . ' - ' . $user_command->getDescription() . PHP_EOL;
            }

            // if ($safe_to_show && count($admin_commands) > 0) {
            //     $text .= PHP_EOL . '*管理员指令列表*:' . PHP_EOL;
            //     foreach ($admin_commands as $admin_command) {
            //         $text .= '/' . $admin_command->getName() . ' - ' . $admin_command->getDescription() . PHP_EOL;
            //     }
            // }

            $text .= PHP_EOL . '想要查询指定指令: /help <指令>';

            return $this->replyToChat($text, ['parse_mode' => 'markdown']);
        }

        $command_str = str_replace('/', '', $command_str);
        if (isset($all_commands[$command_str]) && ($safe_to_show || !$all_commands[$command_str]->isAdminCommand())) {
            $command = $all_commands[$command_str];

            return $this->replyToChat(sprintf(
                '指令: %s (v%s)' . PHP_EOL .
                    '描述: %s' . PHP_EOL .
                    '用法: %s',
                $command->getName(),
                $command->getVersion(),
                $command->getDescription(),
                $command->getUsage()
            ), ['parse_mode' => 'markdown']);
        }

        return $this->replyToChat('无法找到: 指令 `/' . $command_str . '` not found', ['parse_mode' => 'markdown']);
    }

    protected function getUserAndAdminCommands(): array
    {
        $all_commands = $this->telegram->getCommandsList();

        $commands = array_filter($all_commands, function ($command): bool {
            return !$command->isSystemCommand() && $command->showInHelp() && $command->isEnabled();
        });

        $user_commands = array_filter($commands, function ($command): bool {
            return $command->isUserCommand();
        });

        $admin_commands = array_filter($commands, function ($command): bool {
            return $command->isAdminCommand();
        });

        ksort($commands);
        ksort($user_commands);
        ksort($admin_commands);

        return [$commands, $user_commands, $admin_commands];
    }
}
