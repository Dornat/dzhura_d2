<?php

namespace App\Discord\SlashCommands;

use App\Lfg;
use Closure;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Interactions\Interaction;

class LfgDeleteSlashCommandListener implements SlashCommandListenerInterface
{
    public function listen(): Closure
    {
        return function (Interaction $interaction) {
            $groupId = $interaction->data->options['id']->value;
            $userId = $interaction->member->user->id;
            $lfg = Lfg::find($groupId);
            if (empty($lfg)) {
                return $interaction->respondWithMessage(MessageBuilder::new()->setContent('Групи з таким ідентифікатором не існує.'), true);
            }
            if ($lfg->owner === $userId) {
                $interaction->channel->deleteMessages([$lfg->discord_id]);
                $lfg->delete();
                return $interaction->respondWithMessage(MessageBuilder::new()->setContent("Я успішно видалив групу під ідентифікатором: $groupId."), true);
            } else {
                return $interaction->respondWithMessage(MessageBuilder::new()->setContent('Я не видалив цю групу, тому що ти не є її ініціатором.'), true);
            }
        };
    }
}
