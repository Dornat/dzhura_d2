<?php

namespace App\Discord\Helpers;

use App\Discord\SlashCommands\Settings\Objects\SettingsObject;
use App\Discord\SlashCommands\Settings\SelectMenuChannels;
use App\Discord\SlashCommands\Settings\SelectMenuRoles;
use App\Setting;
use DateTimeImmutable;
use Discord\Builders\Components\ActionRow;
use Discord\Builders\Components\Button;
use Discord\Builders\Components\Component;
use Discord\Builders\Components\Option;
use Discord\Builders\Components\SelectMenu;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Channel\Message;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Embed\Field;
use Discord\Parts\Interactions\Interaction;
use Discord\Repository\Interaction\ComponentRepository;

class SlashCommandHelper
{
    public static function assembleAtUsersString(array $userIds): string
    {
        return implode(', ', array_map(function ($id) {
            return "<@$id>";
        }, $userIds));
    }

    public static function assembleAtRoleString(array $roleIds): string
    {
        return implode(', ', array_map(function ($id) {
            return "<@&$id>";
        }, $roleIds));
    }

    public static function constructEmbedActionRowFromComponentRepository(ComponentRepository $componentRepository): ActionRow
    {
        $embedActionRow = ActionRow::new();
        foreach ($componentRepository as $component) {
            $btn = Button::new($component->style, $component->custom_id);
            if ($component->label) {
                $btn->setLabel($component->label);
            }
            if ($component->emoji) {
                $btn->setEmoji($component->emoji);
            }
            if ($component->url) {
                $btn->setUrl($component->url);
            }
            $embedActionRow->addComponent($btn);
        }

        return $embedActionRow;
    }

    public static function constructComponentsForMessageBuilderFromInteraction(?Interaction $interaction, ?Message $message = null): array
    {
        $result = [];
        if (is_null($interaction) && !is_null($message)) {
            $interactionMessage = $message;
        } elseif (!is_null($interaction)) {
            $interactionMessage = $interaction->message;
        } else {
            return $result;
        }

        foreach ($interactionMessage->components as $requestComponent) {
            $btns = [];
            foreach ($requestComponent->components as $component) {
                switch ($component->type) {
                    case Component::TYPE_SELECT_MENU:
                        $selectComponent = SelectMenu::new($component->custom_id)
                            ->setPlaceholder($component->placeholder)
                            ->setMinValues($component->min_values)
                            ->setMaxValues($component->max_values);

                        foreach ($component->options as $option) {
                            $selectComponent->addOption(new Option($option->label, $option->value));
                        }
                        $result[] = $selectComponent;
                        break;
                    case SelectMenuChannels::TYPE_SELECT_MENU_CHANNELS:
                        $result[] = SelectMenuChannels::new($component->custom_id)
                            ->setChannelTypes([SelectMenuChannels::GUILD_TEXT_CHANNEL_TYPE])
                            ->setPlaceholder($component->placeholder)
                            ->setMinValues($component->min_values)
                            ->setMaxValues($component->max_values);
                        break;
                    case SelectMenuRoles::TYPE_SELECT_MENU_ROLES:
                        $result[] = SelectMenuRoles::new($component->custom_id)
                            ->setPlaceholder($component->placeholder)
                            ->setMinValues($component->min_values)
                            ->setMaxValues($component->max_values);
                        break;
                    case Component::TYPE_BUTTON:
                        $btns[] = Button::new($component->style, $component->custom_id)
                                ->setLabel($component->label);
                        break;
                }
            }

            if (!empty($btns)) {
                $row = ActionRow::new();
                foreach ($btns as $btn) {
                    $row->addComponent($btn);
                }
                $result[] = $row;
            }
        }

        return $result;
    }

    public static function updateSettingsModelAndEmbed(SettingsObject $settingsObject, Setting $settingsModel, Interaction $interaction, array $assembledEmbedFields): void
    {
        $settingsModel->object = json_encode($settingsObject);
        $settingsModel->updated_by = $interaction->member->user->id;
        $settingsModel->save();

        /** @var Embed $newEmbed */
        $newEmbed = $interaction->message->embeds->first();
        /** @var Field $field */
        $newEmbed->offsetUnset('fields');
        $newEmbed->addField(...$assembledEmbedFields);

        $interaction->updateMessage(
            MessageBuilder::new()
                ->setContent($interaction->message->content)
                ->addEmbed($newEmbed)
                ->setComponents(self::constructComponentsForMessageBuilderFromInteraction($interaction))
        );
    }

    public static function getCreationDateFromSnowflake(string $snowflake): DateTimeImmutable
    {
        // Discord's epoch: Jan 1, 2015 (in milliseconds)
        $discordEpoch = 1420070400000;
        // Convert snowflake to integer, then right-shift 22 bits, then add epoch
        $timestamp = ((intval($snowflake) >> 22) + $discordEpoch) / 1000;
        return (new DateTimeImmutable())->setTimestamp($timestamp);
    }
}
