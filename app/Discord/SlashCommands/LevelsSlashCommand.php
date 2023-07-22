<?php

namespace App\Discord\SlashCommands;

use App\Discord\SlashCommands\Levels\LevelingSystem;
use App\Discord\SlashCommands\Levels\LevelingXPRewards;
use App\Discord\SlashCommands\Settings\Objects\Levels\AnnouncementChannelEnum;
use App\Discord\SlashCommands\Settings\Objects\SettingsObject;
use App\Level;
use Carbon\Carbon;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Http\Exceptions\NoPermissionsException;
use Discord\InteractionType;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;
use Exception;
use Illuminate\Support\Facades\Log;

class LevelsSlashCommand implements SlashCommandListenerInterface
{
    public const LEVELS = 'levels';

    public const GIVE_XP = 'give-xp';

    public static function act(Interaction $interaction, Discord $discord): void
    {
        if (!($interaction->type === InteractionType::APPLICATION_COMMAND && $interaction->data->name === self::LEVELS)) {
            return;
        }

        if ($interaction->data->options->first()->name === self::GIVE_XP) {
            self::actOnGiveXPCommand($interaction, $discord);
        }
    }

    private static function actOnGiveXPCommand(Interaction $interaction, Discord $discord): void
    {
        if (!$interaction->member->permissions->administrator) {
            $interaction->respondWithMessage(MessageBuilder::new()->setContent('Щось не схоже, що ти маєш права адміністратора. 👁'), true);
            return;
        }

        $userId = $interaction->data->options->first()->options['member']->value;
        $amount = $interaction->data->options->first()->options['amount']->value;

        $levelModel = Level::where('guild_id', $interaction->guild_id)->where('user_id', $userId)->first();
        $levelModel->xp_total += $amount;
        $levelModel->xp_current += $amount;

        if ($levelModel->xp_current >= LevelingXPRewards::neededToLevelUp()[$levelModel->level]) {
            $levelsGained = 0;
            $xpCurrentGained = $levelModel->xp_current;
            for ($i = $levelModel->level; $xpCurrentGained >= LevelingXPRewards::neededToLevelUp()[$i]; $i++) {
                $xpCurrentGained -= LevelingXPRewards::neededToLevelUp()[$i];
                $levelsGained++;
            }
            $levelModel->level += $levelsGained;
            $levelModel->xp_current = max($xpCurrentGained, 0);
            $levelModel->save();
            $guild = $discord->guilds->get('id', $interaction->guild_id);
            try {
                $guild->members->fetch($userId, true)->then(function (Member $member) use ($interaction, $discord, $userId, $guild, $levelModel) {
                    $settingsObject = SettingsObject::getFromGuildId($guild->id);
                    self::levelUpAnnouncement($interaction, $discord, $settingsObject, $userId, $levelModel->level);
                    LevelingSystem::roleRewards($member, $discord, $settingsObject, $levelModel->level);
                    $interaction->respondWithMessage(MessageBuilder::new()->setContent('Користувачу <@' . $userId . '> було успішно надано XP поінти.'), true);
                });
            } catch (Exception $e) {
            }
        } else {
            $levelModel->save();
            $interaction->respondWithMessage(MessageBuilder::new()->setContent('Користувачу <@' . $userId . '> було успішно надано XP поінти.'), true);
        }
    }

    private static function levelUpAnnouncement(Interaction $interaction, Discord $discord, SettingsObject $settingsObject, string $userId, int $level): void
    {
        if ($settingsObject->levels->levelUpAnnouncement->channel === AnnouncementChannelEnum::DISABLED) {
            return;
        }

        $messageAnnouncement = str_replace(
            ['{player}', '{level}'],
            ["<@$userId>", strval($level)],
            $settingsObject->levels->levelUpAnnouncement->announcementMessage
        );

        if ($settingsObject->levels->levelUpAnnouncement->channel === AnnouncementChannelEnum::PRIVATE_MESSAGE) {
            $ownerUser = new User($discord, ['id' => $userId]);
            $ownerUser->sendMessage(
                MessageBuilder::new()
                    ->setContent($messageAnnouncement)
            );
        } else {
            $channel = $discord->getChannel(
                $settingsObject->levels->levelUpAnnouncement->channel === AnnouncementChannelEnum::CURRENT_CHANNEL
                    ? $interaction->channel_id
                    : $settingsObject->levels->levelUpAnnouncement->customChannel->id
            );
            try {
                $channel->sendMessage(
                    MessageBuilder::new()->setContent($messageAnnouncement)
                )->then(function () use ($interaction, $userId) {
                    $interaction->respondWithMessage(MessageBuilder::new()->setContent('Користувачу <@' . $userId . '> було успішно надано XP поінти.'), true);
                }, function () use ($interaction, $messageAnnouncement, $settingsObject) {
                    $interaction->respondWithMessage(MessageBuilder::new()->setContent($messageAnnouncement . "\n\n ❗ Я бачу, що на сервері налаштовано відправку цього повідомлення в <#" . $settingsObject->levels->levelUpAnnouncement->customChannel->id . ">, але я не маю дозволу на відправку повідомлень в цей канал."));
                });
            } catch (NoPermissionsException $e) {
                $interaction->respondWithMessage(MessageBuilder::new()->setContent($messageAnnouncement . "\n\n ❗ Я бачу, що на сервері налаштовано відправку цього повідомлення в <#" . $settingsObject->levels->levelUpAnnouncement->customChannel->id . ">, але я не маю дозволу на відправку повідомлень в цей канал."));
            }
        }
    }
}