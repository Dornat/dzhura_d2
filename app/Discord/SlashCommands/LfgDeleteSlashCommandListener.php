<?php

namespace App\Discord\SlashCommands;

use App\Lfg;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\InteractionType;
use Discord\Parts\Interactions\Interaction;

class LfgDeleteSlashCommandListener implements SlashCommandListenerInterface
{
    public const LFG_DELETE = 'lfgdelete';

    public static function act(Interaction $interaction, Discord $discord): void
    {
        if (!($interaction->type === InteractionType::APPLICATION_COMMAND && $interaction->data->name === self::LFG_DELETE)) {
            return;
        }

        $groupId = $interaction->data->options['id']->value;
        $userId = $interaction->member->user->id;

        $lfg = Lfg::find($groupId);
        if (empty($lfg)) {
            $interaction->respondWithMessage(MessageBuilder::new()->setContent('Групи з таким ідентифікатором не існує.'), true);
            return;
        }

        if ($lfg->owner === $userId) {
            $interaction->channel->deleteMessages([$lfg->discord_id]);
            $lfg->delete();
            $interaction->respondWithMessage(MessageBuilder::new()->setContent("Я успішно видалив групу під ідентифікатором: $groupId."), true);
        } else {
            $interaction->respondWithMessage(MessageBuilder::new()->setContent('Я не видалив цю групу, тому що ти не є її ініціатором.'), true);
        }

        $interaction->acknowledge();
    }
}
