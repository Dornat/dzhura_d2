<?php

namespace App\Discord\Services;

use App\Discord\Helpers\SlashCommandHelper;
use App\Discord\SlashCommands\Settings\Objects\SettingsObject;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Http\Exceptions\NoPermissionsException;
use Discord\Parts\Embed\Embed;
use Discord\Parts\User\Member;
use Illuminate\Support\Facades\Log;

class ServerLogsService
{
    public static function actOnGuildMemberAddEvent(Member $member, Discord $discord, SettingsObject $settings): void
    {
        $embed = new Embed($discord);
        $embed->setColor('#10e06e');
        $embed->setAuthor($member->username, $member->user->avatar);
        $embed->setThumbnail($member->user->avatar);
        $embed->setDescription('🔰 <@' . $member->id . '> доєднався до серверу!');
        $embed->addFieldValues('Акаунт створено', SlashCommandHelper::getCreationDateFromSnowflake($member->id)->format('d.m.Y'));
        $embed->setFooter('User ID: ' . $member->id);

        $channel = $discord->getChannel($settings->serverLogs->sendMessagesChannel);

        try {
            $channel->sendMessage(
                MessageBuilder::new()->addEmbed($embed)
            );
        } catch (NoPermissionsException $e) {
            Log::warning(class_basename(static::class) . ": Could not send message to the channel[" . $settings->serverLogs->sendMessagesChannel . "]", ['error' => $e->getMessage()]);
        }
    }

    public static function actOnGuildMemberRemoveEvent(Member $member, Discord $discord, SettingsObject $settings): void
    {
        $embed = new Embed($discord);
        $embed->setColor('#ff0000');
        $embed->setAuthor($member->username, $member->user->avatar);
        $embed->setThumbnail($member->user->avatar);
        $embed->setDescription('💔 <@' . $member->id . '> покинув сервер!');
        $embed->setFooter('User ID: ' . $member->id);

        $channel = $discord->getChannel($settings->serverLogs->sendMessagesChannel);

        try {
            $channel->sendMessage(
                MessageBuilder::new()->addEmbed($embed)
            );
        } catch (NoPermissionsException $e) {
            Log::warning(class_basename(static::class) . ": Could not send message to the channel[" . $settings->serverLogs->sendMessagesChannel . "]", ['error' => $e->getMessage()]);
        }
    }

    public static function actOnGuildMemberKickEvent(Member $kickedMember, Discord $discord, SettingsObject $settings, Member $kickIssuer, string $kickReason): void
    {
        $embed = new Embed($discord);
        $embed->setColor('#ff3c00');
        $embed->setAuthor($kickIssuer->username, $kickIssuer->user->avatar);
        $embed->setThumbnail($kickedMember->user->avatar);
        $embed->setDescription('🚫 <@' . $kickIssuer->id . '> кікнув <@' . $kickedMember->id . '> з серверу.');
        $embed->addFieldValues('Причина', empty($kickReason) ? 'Не вказана' : $kickReason);
        $embed->setFooter('Kicked User ID: ' . $kickedMember->id);

        $channel = $discord->getChannel($settings->serverLogs->sendMessagesChannel);

        try {
            $channel->sendMessage(
                MessageBuilder::new()->addEmbed($embed)
            );
        } catch (NoPermissionsException $e) {
            Log::warning(class_basename(static::class) . ": Could not send message to the channel[" . $settings->serverLogs->sendMessagesChannel . "]", ['error' => $e->getMessage()]);
        }
    }

    public static function actOnGuildMemberBanEvent(Member $kickedMember, Discord $discord, SettingsObject $settings, Member $kickIssuer, string $kickReason): void
    {
        $embed = new Embed($discord);
        $embed->setColor('#ff0000');
        $embed->setAuthor($kickIssuer->username, $kickIssuer->user->avatar);
        $embed->setThumbnail($kickedMember->user->avatar);
        $embed->setDescription('🚷 <@' . $kickIssuer->id . '> відправив у баню <@' . $kickedMember->id . '>.');
        $embed->addFieldValues('Причина', empty($kickReason) ? 'Не вказана' : $kickReason);
        $embed->setFooter('Banned User ID: ' . $kickedMember->id);

        $channel = $discord->getChannel($settings->serverLogs->sendMessagesChannel);

        try {
            $channel->sendMessage(
                MessageBuilder::new()->addEmbed($embed)
            );
        } catch (NoPermissionsException $e) {
            Log::warning(class_basename(static::class) . ": Could not send message to the channel[" . $settings->serverLogs->sendMessagesChannel . "]", ['error' => $e->getMessage()]);
        }
    }

    public static function actOnGuildMemberUpdateEvent(Member $member, Discord $discord, ?Member $oldMember, SettingsObject $settings): void
    {
        if ($oldMember === null) {
            return;
        }

        $embed = new Embed($discord);

        if ($member->nick !== $oldMember->nick) {
            $embed->setDescription('📝 <@' . $member->id . '> змінив свій нік.');
            $embed->addFieldValues('Старий нік', $oldMember->nick ?? '`пусто`');
            $embed->addFieldValues('Новий нік', $member->nick ?? '`пусто`');
        } else if ($member->username !== $oldMember->username) {
            $embed->setDescription('📝 <@' . $member->id . '> змінив своє ім\'я користувача.');
            $embed->addFieldValues('Старе ім\'я користувача', $oldMember->username ?? '`пусто`');
            $embed->addFieldValues('Нове ім\'я користувача', $member->username ?? '`пусто`');
        } else {
            return;
        }

        $embed->setColor('#ffc400');
        $embed->setAuthor($member->username, $member->user->avatar);
        $embed->setThumbnail($member->user->avatar);
        $embed->setFooter('User ID: ' . $member->id);


        $channel = $discord->getChannel($settings->serverLogs->sendMessagesChannel);

        try {
            $channel->sendMessage(
                MessageBuilder::new()->addEmbed($embed)
            );
        } catch (NoPermissionsException $e) {
            Log::warning(class_basename(static::class) . ": Could not send message to the channel[" . $settings->serverLogs->sendMessagesChannel . "]", ['error' => $e->getMessage()]);
        }
    }
}
