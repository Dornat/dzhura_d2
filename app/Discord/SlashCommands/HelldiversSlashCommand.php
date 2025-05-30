<?php

namespace App\Discord\SlashCommands;

use App\Discord\Helpers\SlashCommandHelper;
use App\Discord\SlashCommands\Settings\Objects\SettingsObject;
use App\HelldiversLfgVoiceChannel;
use Closure;
use Discord\Builders\Components\ActionRow;
use Discord\Builders\Components\Button;
use Discord\Builders\Components\Option;
use Discord\Builders\Components\SelectMenu;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Http\Exceptions\NoPermissionsException;
use Discord\InteractionType;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\WebSockets\VoiceStateUpdate;
use Exception;
use Illuminate\Support\Facades\Log;
use React\Promise\ExtendedPromiseInterface;

class HelldiversSlashCommand implements SlashCommandListenerInterface
{
    public const HELLDIVERS = 'helldivers';

    public const LFG = 'lfg';

    public const CREATE_HELLDIVERS_LFG_BTN = 'create_helldivers_lfg_btn';
    public const CREATE_HELLDIVERS_LFG_AND_TAG_BTN = 'create_helldivers_lfg_and_tag_btn';
    public const CREATE_HELLDIVERS_LFG_RACE_SELECT = 'create_helldivers_lfg_race_select';
    public const CREATE_HELLDIVERS_LFG_LEVEL_SELECT = 'create_helldivers_lfg_level_select';

    /**
     * @throws NoPermissionsException
     * @throws Exception
     */
    public static function act(Interaction $interaction, Discord $discord): void
    {
        if ($interaction->data->custom_id === HelldiversSlashCommand::CREATE_HELLDIVERS_LFG_BTN) {
            HelldiversSlashCommand::actOnCreateHelldiversLfgBtn($interaction, $discord);
            return;
        } else if ($interaction->data->custom_id === HelldiversSlashCommand::CREATE_HELLDIVERS_LFG_AND_TAG_BTN) {
            HelldiversSlashCommand::actOnCreateHelldiversLfgAndTagBtn($interaction);
            return;
        } else if ($interaction->data->custom_id === HelldiversSlashCommand::CREATE_HELLDIVERS_LFG_RACE_SELECT) {
            HelldiversSlashCommand::actOnCreateHelldiversLfgRaceSelect($interaction);
            return;
        } else if ($interaction->data->custom_id === HelldiversSlashCommand::CREATE_HELLDIVERS_LFG_LEVEL_SELECT) {
            HelldiversSlashCommand::actOnCreateHelldiversLfgLevelsSelect($interaction);
            return;
        } else if (!($interaction->type === InteractionType::APPLICATION_COMMAND && $interaction->data->name === self::HELLDIVERS)) {
            return;
        }

        if ($interaction->data->options->first()->name === self::LFG) {
            self::actOnLfgCommand($interaction, $discord);
        }
    }

    /**
     * @throws NoPermissionsException
     */
    private static function actOnLfgCommand(Interaction $interaction, Discord $discord): void
    {
        if (!$interaction->member->permissions->administrator) {
            $interaction->respondWithMessage(MessageBuilder::new()->setContent('Щось не схоже, що ти маєш права адміністратора. 👁'), true);
            return;
        }

        $embed = new Embed($discord);
        $embed->setColor('#34eb4c');
        $embed->setTitle('Helldivers LFG');
        $embed->setDescription("Для створення групи натисни кнопку `Створити групу`.\n\nДля доєднання до наявної групи натисни на назву каналу.");

        $createLfg = Button::new(Button::STYLE_PRIMARY)->setLabel('Створити групу')->setCustomId(self::CREATE_HELLDIVERS_LFG_BTN);

        $embedActionRow = ActionRow::new();
        $embedActionRow
            ->addComponent($createLfg);

        $message = MessageBuilder::new()->addEmbed($embed)->addComponent($embedActionRow);

        $interaction->channel->sendMessage($message)->done(function (Message $message) {
        }, function (Exception $exception) {
            Log::error(class_basename(static::class) . ': Failed sendMessage', ['exception' => $exception->getMessage()]);
        });

        $interaction->respondWithMessage(MessageBuilder::new()->setContent('Запускаю `/helldivers` `lfg` з дуже розумною пикою... 🗿'))->done(function () use ($interaction) {
            $interaction->deleteOriginalResponse();
        });
    }

    /**
     * @throws Exception
     */
    public static function actOnHelldiversVCLeave(VoiceStateUpdate $newState, Discord $discord, VoiceStateUpdate|null $oldState): void
    {
        if (!empty($oldState)) {
            $vcOldState = HelldiversLfgVoiceChannel::where('vc_discord_id', $oldState['channel_id'])->first();

            if (empty($vcOldState)) {
                return;
            }

            $participants = json_decode($vcOldState->participants, true);
            foreach ($participants as $key => $value) {
                if (!empty($value) && $value === $newState['user_id']) {
                    $participants[$key] = '';
                    break;
                }
            }

            self::participantsHandler($participants, $vcOldState, $discord);
        }
    }

    /**
     * @throws Exception
     */
    public static function actOnHelldiversVCEnter(VoiceStateUpdate $newState, Discord $discord, VoiceStateUpdate|null $oldState): void
    {
        if (!is_null($oldState) && $newState['channel_id'] === $oldState['channel_id']) {
            return;
        }

        $vc = HelldiversLfgVoiceChannel::where('vc_discord_id', $newState['channel_id'])->first();
        if (empty($vc)) {
            return;
        }

        $participants = json_decode($vc->participants, true);
        foreach ($participants as $key => $value) {
            if (empty($value)) {
                $participants[$key] = $newState['user_id'];
                break;
            }
        }

        self::participantsHandler($participants, $vc, $discord);
    }

    public static function reRenderLfgEmbed(Message $message): ExtendedPromiseInterface
    {
        $vcs = HelldiversLfgVoiceChannel::where('lfg_message_id', $message->id)->get();
        if (empty($vcs)) {
            return \React\Promise\resolve();
        }

        $embed = $message->embeds->first();
        $embed->offsetUnset('fields');

        foreach ($vcs as $vc) {
            $embed->addFieldValues("<#$vc->vc_discord_id>", self::transpileParticipantsForEmbed(json_decode($vc->participants, true)));
        }

        $embedActionRows = [];
        foreach ($message->components as $component) {
            $embedActionRows[] = SlashCommandHelper::constructEmbedActionRowFromComponentRepository($component->components);
        }
        return $message->edit(MessageBuilder::new()->addEmbed($embed)->setComponents($embedActionRows));
    }

    /**
     * @throws Exception
     */
    public static function actOnCreateHelldiversLfgBtn(Interaction $interaction, Discord $discord): void
    {
        $settingsObject = SettingsObject::getFromInteractionOrGetDefault($interaction);

        if (empty($settingsObject->helldivers->permittedRoles) && !$interaction->member->permissions->administrator) {
            $interaction->respondWithMessage(MessageBuilder::new()->setContent('Недостатньо дозволів для виконання операції. :thinking_face:'), true);
            return;
        }
        if (!empty($settingsObject->helldivers->permittedRoles) && !$interaction->member->permissions->administrator) {
            if (empty(array_intersect(array_column($settingsObject->helldivers->permittedRoles, 'id'), array_keys($interaction->member->roles->jsonSerialize())))) {
                $interaction->respondWithMessage(MessageBuilder::new()->setContent('Недостатньо дозволів для виконання операції. :thinking_face:'), true);
                return;
            }
        }

        $raceSelect = SelectMenu::new(self::CREATE_HELLDIVERS_LFG_RACE_SELECT);
        $raceSelect->setPlaceholder('Роль раси, котру тегнути');
        foreach ($settingsObject->helldivers->racesRoles as $raceRole) {
            $raceSelect->addOption(Option::new($raceRole['name'], $raceRole['id']));
        }

        $levelSelect = SelectMenu::new(self::CREATE_HELLDIVERS_LFG_LEVEL_SELECT);
        $levelSelect->setPlaceholder('Роль рівня, котрий тегнути');
        foreach ($settingsObject->helldivers->levelsRoles as $levelRole) {
            $levelSelect->addOption(Option::new($levelRole['name'], $levelRole['id']));
        }

        $embed = new Embed($discord);
        $embed->setColor('#f58442');
        $embed->setTitle('Останній крок');
        $embed->setDescription("Обери ролі, які треба буде тегнути після створення групи.");
        $embed->addFieldValues('Раса', '');
        $embed->addFieldValues('Рівень', '');
        $embed->setFooter($interaction->message->id);

        $createLfgAndTagBtn = Button::new(Button::STYLE_SUCCESS)
            ->setLabel('Створити групу і тегнути')
            ->setCustomId(self::CREATE_HELLDIVERS_LFG_AND_TAG_BTN);

        $msgActionRow = ActionRow::new();
        $msgActionRow
            ->addComponent($createLfgAndTagBtn);

        $msg = MessageBuilder::new()
            ->addEmbed($embed)
            ->setComponents([
                $raceSelect,
                $levelSelect,
                $msgActionRow,
            ]);

        $interaction->respondWithMessage($msg, true);

    }

    public static function actOnCreateHelldiversLfgRaceSelect(Interaction $interaction): void
    {
        /** @var Embed $embed */
        $embed = $interaction->message->embeds->first();
        $raceField = $embed['fields']['Раса'];
        $levelField = $embed['fields']['Рівень'];
        $embed->offsetUnset('fields');

        $settingsObject = SettingsObject::getFromInteractionOrGetDefault($interaction);
        $racesRoles = $settingsObject->helldivers->racesRoles;
        $key = array_search($interaction['data']['values'][0], array_column($racesRoles, 'id'));
        $raceField['value'] = SlashCommandHelper::assembleAtRoleString($interaction['data']['values']) . ":" . $racesRoles[$key]['name'];

        $embed->addField($raceField);
        $embed->addField($levelField);

        $interaction->updateMessage(
            MessageBuilder::new()
                ->setContent($interaction->message->content)
                ->addEmbed($embed)
                ->setComponents(SlashCommandHelper::constructComponentsForMessageBuilderFromInteraction($interaction))
        );
    }

    public static function actOnCreateHelldiversLfgLevelsSelect(Interaction $interaction): void
    {
        /** @var Embed $embed */
        $embed = $interaction->message->embeds->first();
        $raceField = $embed['fields']['Раса'];
        $levelField = $embed['fields']['Рівень'];
        $embed->offsetUnset('fields');

        $settingsObject = SettingsObject::getFromInteractionOrGetDefault($interaction);
        $levelsRoles = $settingsObject->helldivers->levelsRoles;
        $key = array_search($interaction['data']['values'][0], array_column($levelsRoles, 'id'));
        $levelField['value'] = SlashCommandHelper::assembleAtRoleString($interaction['data']['values']) . ":" . $levelsRoles[$key]['name'];

        $embed->addField($raceField);
        $embed->addField($levelField);

        $interaction->updateMessage(
            MessageBuilder::new()
                ->setContent($interaction->message->content)
                ->addEmbed($embed)
                ->setComponents(SlashCommandHelper::constructComponentsForMessageBuilderFromInteraction($interaction))
        );

    }

    /**
     * @throws Exception
     */
    public static function actOnCreateHelldiversLfgAndTagBtn(Interaction $interaction): void
    {
        $embed = $interaction->message->embeds->first();
        $lfgMessageId = $embed->footer?->text;
        $raceField = $embed['fields']['Раса'];
        $levelField = $embed['fields']['Рівень'];

        $raceRole = empty($raceField['value']) ? null : explode(':', $raceField['value']);
        $levelRole = empty($levelField['value']) ? null : explode(':', $levelField['value']);

        if (empty($raceField['value']) && empty($levelField['value'])) {
            $interaction->respondWithMessage(
                MessageBuilder::new()->setContent('Натисни на селектори і обери одну або дві ролі, які треба буде тегнути після створення групи.'),
                true
            );
            return;
        }

        $settingsObject = SettingsObject::getFromInteractionOrGetDefault($interaction);
        $category = $settingsObject->helldivers->vcCategory;
        $player = $interaction->member->nick ?? $interaction->member->username;

        $channelName = str_replace(
            ['{player}', '{race}', '{level}'],
            [$player, empty($raceRole) ? null : $raceRole[1], empty($levelRole) ? null : $levelRole[1]],
            $settingsObject->helldivers->vcName
        );

        $channelCategory = $interaction->guild->channels->find(function (Channel $channel) use ($category) {
            if ($channel->type === Channel::TYPE_CATEGORY && strtolower($channel->name) === strtolower($category)) {
                return $channel;
            }
            return null;
        });

        $alreadyCreatedVc = HelldiversLfgVoiceChannel::where('guild_id', $interaction->guild_id)->where('owner', $interaction->member->user->id)?->get();

        if (!$alreadyCreatedVc->isEmpty()) {
            /** @var HelldiversLfgVoiceChannel $hdLfgVc */
            $hdLfgVc = $alreadyCreatedVc->first();
            if ($hdLfgVc->vc_rename_count > 0 && !is_null($hdLfgVc->vc_rename_date) && !$hdLfgVc->vc_rename_date->addMinutes(10)->isPast()) {
                $interaction->respondWithMessage(MessageBuilder::new()->setContent('Боги дискорду кажуть, що ліміт на створення чи перестворення групи досягнуто, почекай будь ласка 10 хвилин або створи групу заново після автоматичної очистки.'), true);
                return;
            }
            $interaction->guild->channels->fetch($hdLfgVc->vc_discord_id)->done(function (Channel $channel) use ($interaction, $channelName, $hdLfgVc) {
                $channel->name = $channelName;
                $interaction->guild->channels->save($channel)->done(function () use ($interaction, $channelName, $hdLfgVc) {
                    $hdLfgVc->name = $channelName;
                    if (is_null($hdLfgVc->vc_rename_date) || $hdLfgVc->vc_rename_date->addMinutes(10)->isPast()) {
                        $hdLfgVc->vc_rename_count = 1;
                        $hdLfgVc->vc_rename_date = now();
                    } else if (!$hdLfgVc->vc_rename_date->addMinutes(10)->isPast()) {
                        $hdLfgVc->vc_rename_count = $hdLfgVc->vc_rename_count + 1;
                    }
                    $hdLfgVc->save();

                    $interaction->channel->messages->fetch($hdLfgVc->lfg_message_id)->then(self::handleVcEmbedTag($interaction, $hdLfgVc));
                }, function (Exception $exception) {
                    Log::error(class_basename(static::class) . ': Failed to update channel name', ['exception' => $exception->getMessage()]);
                });
            }, function (Exception $exception) {
                Log::error(class_basename(static::class) . ': Failed to fetch a channel', ['exception' => $exception->getMessage()]);
            });
        } else {
            $newVc = $interaction->guild->channels->create([
                'name' => $channelName,
                'type' => Channel::TYPE_VOICE,
                'user_limit' => 4,
                'parent_id' => $channelCategory?->id
            ]);

            $interaction->guild->channels->save($newVc)->done(function (Channel $channel) use ($interaction, $lfgMessageId) {
                $newVc = new HelldiversLfgVoiceChannel([
                    'guild_id' => $interaction->guild_id,
                    'lfg_channel_id' => $interaction->channel_id,
                    'lfg_message_id' => $lfgMessageId,
                    'vc_discord_id' => $channel->id,
                    'owner' => $interaction->member->user->id,
                    'name' => $channel->name,
                    'user_limit' => $channel->user_limit,
                    'category' => $channel->parent_id,
                    'participants' => json_encode(self::generateEmptyParticipantsList()),
                ]);

                $newVc->save();

                $interaction->channel->messages->fetch($lfgMessageId)->then(self::handleVcEmbedTag($interaction, $newVc));
            });
        }
    }

    private static function handleVcEmbedTag(Interaction $interaction, HelldiversLfgVoiceChannel $hdLfgVc): Closure
    {
        return function (Message $lfgMessage) use ($interaction, $hdLfgVc) {
            self::reRenderLfgEmbed($lfgMessage)->then(function () use ($interaction, $hdLfgVc) {
                $embed = $interaction->message->embeds->first();
                $raceField = $embed['fields']['Раса'];
                $levelField = $embed['fields']['Рівень'];

                $raceRole = explode(':', $raceField['value']);
                $levelRole = explode(':', $levelField['value']);

                $interaction->channel->sendMessage(join([!empty($raceRole) ? $raceRole[0] : null, !empty($levelRole) ? $levelRole[0] : null]) . ": <#$hdLfgVc->vc_discord_id>")->done(function (Message $message) use ($interaction, $hdLfgVc) {
                    $tagMessageId = $hdLfgVc->tag_message_id;
                    $hdLfgVc->tag_message_id = $message->id;
                    $hdLfgVc->save();

                    if (!empty($tagMessageId)) {
                        $interaction->channel->messages->fetch($tagMessageId)->then(function (Message $tagMessage) use ($interaction) {
                            $interaction->channel->messages->delete($tagMessage);
                        }, function (Exception $exception) {
                            Log::warning(class_basename(static::class) . ': Failed to delete tag message', ['exception' => $exception->getMessage()]);
                        });
                    }
                });

                $interaction->updateMessage(
                    MessageBuilder::new()->setComponents([])->setContent('Створено!')->_setFlags(Message::FLAG_SUPPRESS_EMBED)
                )->done(function () use ($interaction) {
                    $interaction->deleteOriginalResponse();
                });
            });
        };
    }

    private static function generateEmptyParticipantsList(): array
    {
        return [
            1 => '',
            2 => '',
            3 => '',
            4 => '',
        ];
    }

    private static function transpileParticipantsForEmbed(array $participants): string
    {
        $return = '';
        foreach ($participants as $key => $value) {
            if (empty($value)) {
                $return .= "$key. *Вільно*\n";
            } else {
                $return .= "$key. <@$value>\n";
            }
        }
        return $return;
    }

    /**
     * @throws Exception
     */
    public static function participantsHandler(array $participants, HelldiversLfgVoiceChannel $vc, Discord $discord): void
    {
        $vc->participants = json_encode($participants);
        $vc->save();

        $guild = $discord->guilds->get('id', $vc->guild_id);

        $guild->channels->fetch($vc->lfg_channel_id)->then(function (Channel $channel) use ($vc) {
            $message = $channel->messages->get('id', $vc->lfg_message_id);
            if (empty($message)) {
                $channel->messages->fetch($vc->lfg_message_id)->then(function (Message $message) {
                    self::reRenderLfgEmbed($message);
                });
            } else {
                self::reRenderLfgEmbed($message);
            }
        });
    }
}
