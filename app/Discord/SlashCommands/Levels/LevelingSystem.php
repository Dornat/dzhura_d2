<?php

namespace App\Discord\SlashCommands\Levels;

use App\Discord\SlashCommands\Settings\Objects\Levels\AnnouncementChannelEnum;
use App\Discord\SlashCommands\Settings\Objects\Levels\NoXPChannelsObject;
use App\Discord\SlashCommands\Settings\Objects\Levels\NoXPRolesObject;
use App\Discord\SlashCommands\Settings\Objects\Levels\RoleRewardsTypeEnum;
use App\Discord\SlashCommands\Settings\Objects\SettingsObject;
use App\Level;
use Carbon\Carbon;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Http\Exceptions\NoPermissionsException;
use Discord\Parts\Channel\Message;
use Discord\Parts\User\Member;
use Exception;
use Illuminate\Support\Facades\Log;

class LevelingSystem
{
    public const MINIMUM_AWARD = 15;
    public const MAXIMUM_AWARD = 25;

    public static function act(Message $message, Discord $discord): void
    {
        if (!self::isMessageTypeAllowed($message)) {
            return;
        }
        $settingsObject = SettingsObject::getFromGuildId($message->guild_id);
        if (!$settingsObject->levels->active) {
            return;
        }
        if (!self::isChannelAllowed($settingsObject->levels->noXPChannels, $message->channel_id)) {
            return;
        }
        if (!self::isRoleAllowed($settingsObject->levels->noXPRoles, $message->member->roles->toArray())) {
            return;
        }

        self::reward($message, $settingsObject, $discord);
    }

    private static function isMessageTypeAllowed(Message $message): bool
    {
        return !$message->author->bot && ($message->type === Message::TYPE_NORMAL || $message->type === Message::TYPE_REPLY);
    }

    private static function isChannelAllowed(NoXPChannelsObject $noXPChannelsObject, string $channelId): bool
    {
        if ($noXPChannelsObject->allowAllChannels && !in_array($channelId, $noXPChannelsObject->except)) {
            return true;
        }

        if (!$noXPChannelsObject->allowAllChannels && in_array($channelId, $noXPChannelsObject->except)) {
            return true;
        }

        return false;
    }

    private static function isRoleAllowed(NoXPRolesObject $noXPRolesObject, array $roleIds): bool
    {
        if ($noXPRolesObject->allowAllRoles && empty(array_intersect($noXPRolesObject->except, $roleIds))) {
            return true;
        }

        if (!$noXPRolesObject->allowAllRoles && !empty(array_intersect($noXPRolesObject->except, $roleIds))) {
            return true;
        }

        return false;
    }

    private static function reward(Message $message, SettingsObject $settingsObject, Discord $discord): void
    {
        $levelModel = Level::where('guild_id', $message->guild_id)->where('user_id', $message->member->id)->first();
        if (!is_null($levelModel) && Carbon::parse($levelModel->suspended)->gt(Carbon::now())) {
            return;
        }

        if (is_null($levelModel)) {
            $levelModel = new Level();
            $levelModel->guild_id = $message->guild_id;
            $levelModel->user_id = $message->member->id;
            $levelModel->level = 0;
            $levelModel->xp_current = 0;
            $levelModel->xp_total = 0;
            $levelModel->messages = 0;
        }

        $levelModel->messages += 1;
        $xpRewarded = self::xpRewarded($message, $settingsObject);
        $levelModel->xp_total += $xpRewarded;
        $levelModel->suspended = Carbon::now()->addMinutes();
        $levelModel->xp_current += $xpRewarded;

        if ($levelModel->xp_current >= LevelingXPRewards::neededToLevelUp()[$levelModel->level]) {
            $levelModel->xp_current -= LevelingXPRewards::neededToLevelUp()[$levelModel->level];
            $levelModel->level += 1;
            $levelModel->save();
            self::levelUpAnnouncement($message, $discord, $settingsObject, $levelModel->level);
            self::roleRewards($message->member, $discord, $settingsObject, $levelModel->level);
        } else {
            $levelModel->save();
        }
    }

    private static function xpRewarded(Message $message, SettingsObject $settingsObject): int
    {
        $result = rand(self::MINIMUM_AWARD, self::MAXIMUM_AWARD);
        $xpRate = $settingsObject->levels->xpRate->rate->value;
        $roleSpecific = array_values(array_intersect(array_keys($message->member->roles->toArray()), array_keys($settingsObject->levels->xpRate->roleSpecificRate)));
        if (!empty($roleSpecific)) {
            $xpRate = (int)$settingsObject->levels->xpRate->roleSpecificRate[$roleSpecific[0]];
        }
        return round(($result * $xpRate) / 100);
    }

    private static function levelUpAnnouncement(Message $message, Discord $discord, SettingsObject $settingsObject, int $level): void
    {
        if ($settingsObject->levels->levelUpAnnouncement->channel === AnnouncementChannelEnum::DISABLED) {
            return;
        }

        $userId = $message->author->id;
        $messageAnnouncement = str_replace(
            ['{player}', '{level}'],
            ["<@$userId>", strval($level)],
            $settingsObject->levels->levelUpAnnouncement->announcementMessage
        );

        if ($settingsObject->levels->levelUpAnnouncement->channel === AnnouncementChannelEnum::CURRENT_CHANNEL) {
            $message->reply(MessageBuilder::new()->setContent($messageAnnouncement));
        } else if ($settingsObject->levels->levelUpAnnouncement->channel === AnnouncementChannelEnum::PRIVATE_MESSAGE) {
            $message->author->sendMessage(
                MessageBuilder::new()
                    ->setContent($messageAnnouncement)
            );
        } else if ($settingsObject->levels->levelUpAnnouncement->channel === AnnouncementChannelEnum::CUSTOM_CHANNEL) {
            $channel = $discord->getChannel($settingsObject->levels->levelUpAnnouncement->customChannel->id);
            try {
                $channel->sendMessage(
                    MessageBuilder::new()->setContent($messageAnnouncement)
                )->then(function () {
                }, function () use ($message, $messageAnnouncement, $settingsObject) {
                    $message->reply(MessageBuilder::new()->setContent($messageAnnouncement . "\n\n ❗ Я бачу, що на сервері налаштовано відправку цього повідомлення в <#" . $settingsObject->levels->levelUpAnnouncement->customChannel->id . ">, але я не маю дозволу на відправку повідомлень в цей канал."));
                });
            } catch (NoPermissionsException $e) {
                $message->reply(MessageBuilder::new()->setContent($messageAnnouncement . "\n\n ❗ Я бачу, що на сервері налаштовано відправку цього повідомлення в <#" . $settingsObject->levels->levelUpAnnouncement->customChannel->id . ">, але я не маю дозволу на відправку повідомлень в цей канал."));
            }
        }
    }

    public static function roleRewards(Member $member, Discord $discord, SettingsObject $settingsObject, int $level): void
    {
        if (empty($settingsObject->levels->roleRewards->roleRewards)) {
            return;
        }

        $allMemberRoles = array_map('strval', array_keys($member->roles->toArray()));
        $roleRewards = $settingsObject->levels->roleRewards->roleRewards;
        krsort($roleRewards);

        $topMostRole = true;
        foreach ($roleRewards as $lvl => $role) {
            if ($lvl <= $level) {
                if (in_array($role, $allMemberRoles) && !$topMostRole) {
                    if ($settingsObject->levels->roleRewards->roleRewardsType === RoleRewardsTypeEnum::REMOVE_PREVIOUS_REWARDS) {
                        unset($allMemberRoles[array_search($role, $allMemberRoles)]);
                        try {
                            $guild = $discord->guilds->get('id', $member->guild_id);
                            $guild->members->fetch($member->user->id, true)->then(function (Member $member) use ($role, $level) {
                                $member->removeRole($role, "Level: $level");
                            });
                        } catch (Exception $e) {
                        }
                    }
                } else {
                    if (!in_array($role, $allMemberRoles) && ($topMostRole || $settingsObject->levels->roleRewards->roleRewardsType === RoleRewardsTypeEnum::STACK_PREVIOUS_REWARDS)) {
                        $allMemberRoles[] = $role;
                        try {
                            $guild = $discord->guilds->get('id', $member->guild_id);
                            $guild->members->fetch($member->user->id, true)->then(function (Member $member) use ($role, $level) {
                                $member->addRole($role, "Level: $level");
                            });
                        } catch (Exception $e) {
                        }
                    }
                }
                $topMostRole = false;
            }
        }
    }
}