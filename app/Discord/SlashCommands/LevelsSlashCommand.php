<?php

namespace App\Discord\SlashCommands;

use App\Discord\SlashCommands\Levels\LevelingSystem;
use App\Discord\SlashCommands\Levels\LevelingXPRewards;
use App\Discord\SlashCommands\Settings\Objects\Levels\AnnouncementChannelEnum;
use App\Discord\SlashCommands\Settings\Objects\SettingsObject;
use App\Level;
use App\Setting;
use Discord\Builders\Components\ActionRow;
use Discord\Builders\Components\Button;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Http\Exceptions\NoPermissionsException;
use Discord\InteractionType;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use React\Promise\PromiseInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\BufferedOutput;

class LevelsSlashCommand implements SlashCommandListenerInterface
{
    public const LEVELS = 'levels';

    public const GIVE_XP = 'give-xp';
    public const LEADERBOARD = 'leaderboard';
    public const RANK = 'rank';
    public const REMOVE_XP = 'remove-xp';
    public const TABLE = 'table';

    public const REMOVE_RANK_MESSAGE_BTN = 'remove_rank_message_btn';

    public const LEADERBOARD_DEFAULT_LIMIT = 10;
    public const LEADERBOARD_PREV_BTN = 'leaderboard_prev_btn';
    public const LEADERBOARD_NEXT_BTN = 'leaderboard_next_btn';
    public const LEADERBOARD_REMV_BTN = 'leaderboard_remv_btn';

    public static function act(Interaction $interaction, Discord $discord): void
    {
        if ($interaction->data->custom_id === self::REMOVE_RANK_MESSAGE_BTN) {
            self::actOnRemoveRankMessageBtn($interaction);
            return;
        } else if ($interaction->data->custom_id === self::LEADERBOARD_NEXT_BTN) {
            self::actOnLeaderboardNextBtn($interaction, $discord);
            return;
        } else if ($interaction->data->custom_id === self::LEADERBOARD_PREV_BTN) {
            self::actOnLeaderboardPrevBtn($interaction, $discord);
            return;
        } else if ($interaction->data->custom_id === self::LEADERBOARD_REMV_BTN) {
            self::actOnLeaderboardRemvBtn($interaction);
            return;
        }

        if (!($interaction->type === InteractionType::APPLICATION_COMMAND && $interaction->data->name === self::LEVELS)) {
            return;
        }

        if ($interaction->data->options->first()->name === self::GIVE_XP) {
            self::actOnGiveXPCommand($interaction, $discord);
        } else if ($interaction->data->options->first()->name === self::LEADERBOARD) {
            self::actOnLeaderboardCommand($interaction, $discord);
        } else if ($interaction->data->options->first()->name === self::RANK) {
            self::actOnRankCommand($interaction, $discord);
        } else if ($interaction->data->options->first()->name === self::REMOVE_XP) {
            self::actOnRemoveXPCommand($interaction, $discord);
        } else if ($interaction->data->options->first()->name === self::TABLE) {
            self::actOnTableCommand($interaction, $discord);
        }
    }

    private static function isActiveForGuild(string $guildId): bool
    {
        $settingRow = Setting::where('guild_id', $guildId)->first();

        if (!is_null($settingRow)) {
            $settingsObject = new SettingsObject(json_decode($settingRow->object, true));
            return $settingsObject->levels->active;
        }

        return false;
    }

    private static function actOnGiveXPCommand(Interaction $interaction, Discord $discord): void
    {
        if (!$interaction->member->permissions->administrator) {
            $interaction->respondWithMessage(MessageBuilder::new()->setContent('Щось не схоже, що ти маєш права адміністратора. 👁'), true);
            return;
        }
        if (!self::isActiveForGuild($interaction->guild_id)) {
            $interaction->respondWithMessage(MessageBuilder::new()->setContent('Для використання даної команди, потрібно активувати систему левелінгу.'), true);
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

    private static function actOnLeaderboardCommand(Interaction $interaction, Discord $discord): void
    {
        if (!self::isActiveForGuild($interaction->guild_id)) {
            $interaction->respondWithMessage(MessageBuilder::new()->setContent('Для використання даної команди, потрібно активувати систему левелінгу.'), true);
            return;
        }
        self::sendLeaderBoardMessage($interaction, $discord, 1);
    }

    private static function actOnLeaderboardNextBtn(Interaction $interaction, Discord $discord): void
    {
        preg_match('/(\d+)/', $interaction->message->components[0]->components->last()->label, $matches);
        $page = (int)$matches[1];
        self::sendLeaderBoardMessage($interaction, $discord, $page, false);
    }

    private static function actOnLeaderboardPrevBtn(Interaction $interaction, Discord $discord): void
    {
        preg_match('/(\d+)/', $interaction->message->components[0]->components->first()->label, $matches);
        $page = (int)$matches[1];
        self::sendLeaderBoardMessage($interaction, $discord, $page, false);
    }

    private static function actOnLeaderboardRemvBtn(Interaction $interaction): void
    {
        $interaction->message->delete();
    }

    private static function sendLeaderBoardMessage(Interaction $interaction, Discord $discord, int $page, bool $respond = true, int $limit = self::LEADERBOARD_DEFAULT_LIMIT): void
    {
        $guildId = $interaction->guild_id;
        $pageMultiplier = ($page * $limit) - $limit;
        $users = DB::select("SELECT * FROM `levels` WHERE `guild_id` = '$guildId' ORDER BY `xp_total` DESC LIMIT $limit OFFSET $pageMultiplier");
        $usersTotal = DB::select("SELECT COUNT(*) as `count` FROM `levels` WHERE `guild_id` = '$guildId'");
        $pagesTotal = (int)ceil($usersTotal[0]->count / $limit);

        $memberPromises = [];
        foreach ($users as $user) {
            try {
                $memberPromises[] = $interaction->guild->members->fetch($user->user_id)->then(function ($memberFetched) {
                    return $memberFetched;
                }, function () use ($discord, $user) {
                    return $discord->users->fetch($user->user_id)->then(function ($userFetched) use ($discord) {
                        return new Member($discord, [
                            'user' => $userFetched,
                            'username' => $userFetched->username,
                        ]);
                    });
                });
            } catch (Exception $e) {
                Log::error('Could not fetch member', ['method' => __METHOD__, 'userId' => $user->user_id, 'exception' => $e->getMessage()]);
            }
        }

        self::buildPromiseEmbeds($memberPromises, $users, $interaction, $discord, $pageMultiplier)->then(function ($embeds) use ($interaction, $page, $pagesTotal, $respond, $pageMultiplier) {
            $btnActionRow = ActionRow::new()
                ->addComponent(Button::new(Button::STYLE_SECONDARY, self::LEADERBOARD_PREV_BTN)->setLabel('< ' . max($page - 1, 1))->setDisabled($page === 1))
                ->addComponent(Button::new(Button::STYLE_SECONDARY, self::LEADERBOARD_REMV_BTN)->setEmoji('🗑'))
                ->addComponent(Button::new(Button::STYLE_SECONDARY, self::LEADERBOARD_NEXT_BTN)->setLabel(min($page + 1, $pagesTotal) . ' >')->setDisabled($page === $pagesTotal));
            $msg = MessageBuilder::new()->addEmbed(...$embeds)->addComponent($btnActionRow);
            if ($respond) {
                $interaction->respondWithMessage($msg);
            } else {
                $interaction->updateMessage($msg);
            }
        });
    }

    private static function buildPromiseEmbeds(array $memberPromises, array $users, Interaction $interaction, Discord $discord, int $pageMultiplier): PromiseInterface
    {
        return \React\Promise\all($memberPromises)->then(function ($members) use ($users, $interaction, $discord, $pageMultiplier) {
            $embeds = [];

            foreach ($users as $key => $user) {
                $emoji = null;
                $color = '#4e6987';
                $position = $key + 1 + $pageMultiplier;
                if ($position === 1) {
                    $emoji = '🏆';
                    $color = '#FFD700';
                } else if ($position === 2) {
                    $emoji = '🥈';
                    $color = '#C0C0C0';
                } else if ($position === 3) {
                    $emoji = '🥉';
                    $color = '#cd7f32';
                }

                $userName = $members[$key]->nick ?: $members[$key]->username;

                $embed = new Embed($discord);
                $embed->setColor($color);
                $embed->setTitle(($emoji ?: "[$position]") . ' ' . $userName);
                $embed->setThumbnail($members[$key]->user->avatar);
                $embed->addFieldValues('Рівень', "**$user->level**", true);
                $embed->addFieldValues('Загально XP', "**$user->xp_total** XP", true);
                $embed->addFieldValues('Повідомлень', "**$user->messages**");

                $embeds[] = $embed;
            }

            return $embeds;
        });
    }

    private static function actOnRankCommand(Interaction $interaction, Discord $discord): void
    {
        if (!self::isActiveForGuild($interaction->guild_id)) {
            $interaction->respondWithMessage(MessageBuilder::new()->setContent('Для використання даної команди, потрібно активувати систему левелінгу.'), true);
            return;
        }

        $userId = $interaction->data?->options?->first()?->options['member']?->value;
        $userId = $userId ?? $interaction->user->id;

        try {
            $interaction->guild->members->fetch($userId)->then(function (Member $member) use ($interaction, $discord, $userId) {
                $guildId = $interaction->guild_id;
                /** @var Level $levelModel */
                $levelModel = Level::where('guild_id', $guildId)->where('user_id', $userId)->first();
                $rank = DB::select("SELECT user_id, xp_total,
           (SELECT COUNT(*) + 1
            FROM levels AS t2
            WHERE guild_id = '$guildId' AND (t2.xp_total > t1.xp_total OR (t2.xp_total = t1.xp_total AND t2.user_id < t1.user_id))
           ) AS rank
    FROM levels AS t1
    WHERE user_id = '$userId' AND guild_id = '$guildId'");

                $embed = new Embed($discord);
                $embed->setThumbnail($member->user->avatar);
                $embed->setColor('#024ad9');
                $embed->setTitle('Ранг #' . $rank[0]->rank);
                $embed->setDescription('Рівень **' . $levelModel->level . '**');
                $embed->addFieldValues('Поточно', $levelModel->xp_current . ' XP', true);
                $embed->addFieldValues('Всього', $levelModel->xp_total . ' XP', true);
                $embed->addFieldValues('До наступного рівня', LevelingXPRewards::neededToLevelUp()[$levelModel->level] - $levelModel->xp_current . ' XP');
                $embed->addFieldValues('Всього зараховано повідомлень', $levelModel->messages);

                $msg = MessageBuilder::new()->addEmbed($embed);
                $msg->addComponent(
                    ActionRow::new()
                        ->addComponent(
                            Button::new(Button::STYLE_SECONDARY, self::REMOVE_RANK_MESSAGE_BTN)
                                ->setEmoji('🗑')
                        )
                );

                $interaction->respondWithMessage($msg);
            });
        } catch (Exception $e) {
            Log::error('Could not fetch member', ['method' => __METHOD__, 'userId' => $userId, 'exception' => $e->getMessage()]);
        }
    }

    private static function actOnRemoveRankMessageBtn(Interaction $interaction): void
    {
        $interaction->message->delete();
    }

    private static function actOnRemoveXPCommand(Interaction $interaction, Discord $discord): void
    {
        if (!$interaction->member->permissions->administrator) {
            $interaction->respondWithMessage(MessageBuilder::new()->setContent('Щось не схоже, що ти маєш права адміністратора. 👁'), true);
            return;
        }
        if (!self::isActiveForGuild($interaction->guild_id)) {
            $interaction->respondWithMessage(MessageBuilder::new()->setContent('Для використання даної команди, потрібно активувати систему левелінгу.'), true);
            return;
        }

        $userId = $interaction->data->options->first()->options['member']->value;
        $amount = $interaction->data->options->first()->options['amount']->value;

        $levelModel = Level::where('guild_id', $interaction->guild_id)->where('user_id', $userId)->first();
        $levelModel->xp_total = max($levelModel->xp_total - $amount, 0);
        $xpCurrentRemoved = $levelModel->xp_current - $amount;

        if ($xpCurrentRemoved < 0) {
            $levelsToRemove = 0;
            $leftoverXPForCurrLvl = $amount - $levelModel->xp_current;
            $xpCurrentResult = 0;
            for ($i = $levelModel->level - 1; $leftoverXPForCurrLvl > 0; $i--) {
                $xpCurrentResult = LevelingXPRewards::neededToLevelUp()[$i] - $leftoverXPForCurrLvl;
                if ($xpCurrentResult < 0) {
                    $xpCurrentResult = 0;
                }
                $leftoverXPForCurrLvl -= LevelingXPRewards::neededToLevelUp()[$i];
                $levelsToRemove++;
            }
            $levelModel->level -= $levelsToRemove;
            $levelModel->xp_current = $xpCurrentResult;
            $levelModel->save();
            $guild = $discord->guilds->get('id', $interaction->guild_id);
            try {
                $guild->members->fetch($userId, true)->then(function (Member $member) use ($interaction, $discord, $userId, $guild, $levelModel) {
                    $settingsObject = SettingsObject::getFromGuildId($guild->id);
                    if ($settingsObject->levels->roleRewards->removeRoleRewardsOnDemotion) {
                        self::removeRoleRewards($member, $levelModel->level, $settingsObject->levels->roleRewards->roleRewards);
                    }
                    $interaction->respondWithMessage(MessageBuilder::new()->setContent('Користувачу <@' . $userId . '> було успішно знято XP поінти.'), true);
                });
            } catch (Exception $e) {
            }
        } else {
            $levelModel->xp_current = $xpCurrentRemoved;
            $levelModel->save();
            $interaction->respondWithMessage(MessageBuilder::new()->setContent('Користувачу <@' . $userId . '> було успішно знято XP поінти.'), true);
        }
    }

    private static function removeRoleRewards(Member $member, int $level, array $roleRewards): void
    {
        if (empty($roleRewards)) {
            return;
        }
        $allMemberRoles = array_map('strval', array_keys($member->roles->toArray()));

        krsort($roleRewards);

        foreach ($roleRewards as $lvl => $role) {
            if ($lvl > $level && in_array($role, $allMemberRoles)) {
                $member->removeRole($role);
            }
        }
    }

    private static function actOnTableCommand(Interaction $interaction, Discord $discord): void
    {
        if (!self::isActiveForGuild($interaction->guild_id)) {
            $interaction->respondWithMessage(MessageBuilder::new()->setContent('Для використання даної команди, потрібно активувати систему левелінгу.'), true);
            return;
        }
        $bufferedOutput = new BufferedOutput();
        $table = new Table($bufferedOutput);
        $table->setStyle('box-double');
        $tableRows = [];

        for ($lvl = 0; $lvl <= 42; $lvl++) {
            $tableRows[] = [$lvl, LevelingXPRewards::neededToLevelUp()[$lvl], LevelingXPRewards::totalXPOnEachLevel()[$lvl]];
        }

        $table->setHeaders(['Рівень', 'XP потрібно', 'XP загально'])
        ->setRows($tableRows);
        $table->render();

        $str = $bufferedOutput->fetch();

        $interaction->respondWithMessage(MessageBuilder::new()->setContent("```$str```"), true);
    }
}
