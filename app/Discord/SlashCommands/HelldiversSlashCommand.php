<?php

namespace App\Discord\SlashCommands;

use App\Discord\Helpers\SlashCommandHelper;
use App\Discord\SlashCommands\Settings\Objects\SettingsObject;
use App\HelldiversLfgVoiceChannel;
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
            Log::error('Failed sendMessage', ['message' => $exception->getMessage(), 'e' => json_encode($exception)]);
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
        if (!empty($oldState) && empty($newState['channel_id'])) {
            $vc = HelldiversLfgVoiceChannel::where('vc_discord_id', $oldState['channel_id'])->first();

            if (empty($vc)) {
                return;
            }

            $guild = $discord->guilds->get('id', $newState['guild_id']);

            if ($vc->owner !== $newState['user_id']) {
                $participants = json_decode($vc->participants, true);
                foreach ($participants as $key => $value) {
                    if (!empty($value) && $value === $newState['user_id']) {
                        $participants[$key] = '';
                        break;
                    }
                }

                self::participantsHandler($participants, $vc, $discord);
            } else {
                $guild->channels->delete($oldState['channel_id'])->done(function () use ($vc, $guild) {
                    $vc->delete();

                    $guild->channels->fetch($vc->lfg_channel_id)->then(function (Channel $channel) use ($vc) {
                        $message = $channel->messages->get('id', $vc->lfg_message_id);
                        if (empty($message)) {
                            $channel->messages->fetch($vc->lfg_message_id)->then(function (Message $message){
                                self::reRenderLfgEmbed($message);
                            });
                        } else {
                            self::reRenderLfgEmbed($message);
                        }
                    });
                });
            }
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

    public static function reRenderLfgEmbed(Message $message): void
    {
        $vcs = HelldiversLfgVoiceChannel::where('lfg_message_id', $message->id)->get();
        if (empty($vcs)) {
            return;
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
        $message->edit(MessageBuilder::new()->addEmbed($embed)->setComponents($embedActionRows));
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

        $createdVcsForOwnerCount = HelldiversLfgVoiceChannel::where('owner', $interaction->member->user->id)?->get()?->count();
        if ((!empty($createdVcsForOwnerCount) || $createdVcsForOwnerCount === 0) && !$interaction->member->permissions->administrator) {
            if ($settingsObject->helldivers->vcLimit <= $createdVcsForOwnerCount) {
                $interaction->respondWithMessage(MessageBuilder::new()->setContent('Досягнуто ліміту кількості створення голосових каналів. :face_with_monocle:'), true);
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

        $createLfgAndTagBtn = Button::new(Button::STYLE_SUCCESS)
            ->setLabel('Створити групу і тегнути')
            ->setCustomId(self::CREATE_HELLDIVERS_LFG_AND_TAG_BTN)
            ->setListener(self::actOnCreateHelldiversLfgAndTagBtn($interaction), $discord, true);

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

        $raceField['value'] = SlashCommandHelper::assembleAtRoleString($interaction['data']['values']);

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

        $levelField['value'] = SlashCommandHelper::assembleAtRoleString($interaction['data']['values']);

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
    public static function actOnCreateHelldiversLfgAndTagBtn(Interaction $prevInteraction): callable
    {
        return function (Interaction $interaction) use ($prevInteraction) {
            $settingsObject = SettingsObject::getFromInteractionOrGetDefault($interaction);
            $category = $settingsObject->helldivers->vcCategory;
            $player = $interaction->member->nick ?? $interaction->member->username;
            $channelName = str_replace(['{player}'], [$player], $settingsObject->helldivers->vcName);

            $channelCategory = $interaction->guild->channels->find(function (Channel $channel) use ($category) {
                if ($channel->type === Channel::TYPE_CATEGORY && strtolower($channel->name) === strtolower($category)) {
                    return $channel;
                }
                return null;
            });

            $newVc = $interaction->guild->channels->create([
                'name' => $channelName,
                'type' => Channel::TYPE_VOICE,
                'user_limit' => 4,
                'parent_id' => $channelCategory?->id
            ]);

            $interaction->guild->channels->save($newVc)->done(function (Channel $channel) use ($interaction, $prevInteraction) {
                $newVc = new HelldiversLfgVoiceChannel([
                    'guild_id' => $prevInteraction->guild_id,
                    'lfg_channel_id' => $prevInteraction->channel_id,
                    'lfg_message_id' => $prevInteraction['message']['id'],
                    'vc_discord_id' => $channel->id,
                    'owner' => $prevInteraction->member->user->id,
                    'name' => $channel->name,
                    'user_limit' => $channel->user_limit,
                    'category' => $channel->parent_id,
                    'participants' => json_encode(self::generateEmptyParticipantsList()),
                ]);

                $newVc->save();

                /** @var Embed $embed */
                $embed = $prevInteraction->message->embeds->first();
                $participantsList = json_decode($newVc->participants, true);
                $embed->addFieldValues("<#$channel->id>", self::transpileParticipantsForEmbed($participantsList));

                $components = SlashCommandHelper::constructComponentsForMessageBuilderFromInteraction($prevInteraction);

                $prevInteraction->message->edit(
                    MessageBuilder::new()
                        ->addEmbed($embed)
                        ->setComponents($components)
                )->then(function () use ($interaction, $prevInteraction, $newVc) {
                    $embed = $interaction->message->embeds->first();
                    $raceField = $embed['fields']['Раса'];
                    $levelField = $embed['fields']['Рівень'];
                    $prevInteraction->channel->sendMessage("{$raceField['value']}, {$levelField['value']}: <#$newVc->vc_discord_id>");
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
