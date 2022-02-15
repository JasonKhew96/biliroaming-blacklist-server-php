<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;

class ChoseninlineresultCommand extends SystemCommand
{
    protected $name = 'choseninlineresult';
    protected $description = '内联';
    protected $version = '1.0.0';
    public function execute(): ServerResponse
    {
        $inline_query = $this->getChosenInlineResult();
        $query        = $inline_query->getQuery();

        return parent::execute();
    }
}
