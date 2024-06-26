<?php

namespace App\Discord\SlashCommands\Settings\Factories;

use App\Discord\Helpers\SlashCommandHelper;
use App\Discord\SlashCommands\Settings\Objects\SettingsObject;
use App\Discord\SlashCommands\Settings\SelectMenuRoles;
use App\Setting;
use Discord\Builders\Components\ActionRow;
use Discord\Builders\Components\Button;
use Discord\Builders\Components\TextInput;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Helpers\Collection;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Embed\Field;
use Discord\Parts\Interactions\Interaction;

class VoiceCreateSettingsFactory
{
    public const SETTINGS_VC_CREATION_ROLES_SELECT = 'settings_vc_creation_roles_select';
    public const SETTINGS_VC_OPEN_MODAL_BTN = 'settings_vc_open_modal_btn';
    public const SETTINGS_VC_MODAL = 'settings_vc_modal';
    public const SETTINGS_VC_DEFAULT_CATEGORY_INPUT = 'settings_vc_default_category_input';
    public const SETTINGS_VC_CHANNEL_LIMIT_INPUT = 'settings_vc_channel_limit_input';

    public Embed $embed;
    public ActionRow $modalBtnActionRow;
    public SelectMenuRoles $authorizedRolesSelect;

    public function __construct(Discord $discord, SettingsObject $settingsObject)
    {
        $this->embed = new Embed($discord);
        $this->embed->setColor('#024ad9');
        $this->embed->setTitle('Налаштування команди /voicecreate');
        $this->embed->addFieldValues('Ролі, яким надано доступ до використання команди', SlashCommandHelper::assembleAtRoleString(array_column($settingsObject->vc->permittedRoles, 'id')));
        $this->embed->addFieldValues('Назва дефолтної підкатегорії', $settingsObject->vc->defaultCategory);
        $this->embed->addFieldValues('Ліміт створення голосових каналів', $settingsObject->vc->channelLimit);

        $vcSettingsModalBtn = Button::new(Button::STYLE_PRIMARY)
            ->setLabel('Зміна налаштувань /voicecreate')
            ->setCustomId(self::SETTINGS_VC_OPEN_MODAL_BTN);
        $this->modalBtnActionRow = ActionRow::new()->addComponent($vcSettingsModalBtn);

        $this->authorizedRolesSelect = SelectMenuRoles::new(self::SETTINGS_VC_CREATION_ROLES_SELECT)
            ->setPlaceholder('Ролі для /voicecreate')
            ->setMinValues(0)
            ->setMaxValues(25);
    }

    public static function actOnVCRoleSelect(Interaction $interaction, Discord $discord): void
    {
        list($settingsObject, $settingsModel) = SettingsObject::getFromInteractionOrGetDefault($interaction, true);
        $settingsObject->vc->permittedRoles = [];

        foreach ($interaction->data->resolved->roles as $role) {
            $settingsObject->vc->permittedRoles[] = [
                'id' => $role->id,
                'name' => $role->name
            ];
        }

        /** @var Setting $settingsModel object */
        $settingsModel->object = json_encode($settingsObject);
        $settingsModel->updated_by = $interaction->member->user->id;
        $settingsModel->save();

        /** @var Embed $newEmbed */
        $newEmbed = $interaction->message->embeds->first();
        /** @var Field $field */
        $newEmbed->offsetUnset('fields');
        $newEmbed->addFieldValues('Ролі, яким надано доступ до використання команди', SlashCommandHelper::assembleAtRoleString(array_column($settingsObject->vc->permittedRoles, 'id')));
        $newEmbed->addFieldValues('Назва дефолтної підкатегорії', $settingsObject->vc->defaultCategory);
        $newEmbed->addFieldValues('Ліміт створення голосових каналів', $settingsObject->vc->channelLimit);

        $interaction->updateMessage(
            MessageBuilder::new()
                ->setContent($interaction->message->content)
                ->addEmbed($newEmbed)
                ->setComponents(SlashCommandHelper::constructComponentsForMessageBuilderFromInteraction($interaction))
        );
    }

    public static function actOnVoiceCreateSettingsModalOpenBtn(Interaction $interaction, Discord $discord): void
    {
        $settingsObject = SettingsObject::getFromInteractionOrGetDefault($interaction);

        $defaultCategoryInput = TextInput::new('Назва дефолтної підкатегорії', TextInput::STYLE_SHORT, self::SETTINGS_VC_DEFAULT_CATEGORY_INPUT)
            ->setPlaceholder('Коротка назва (напр. Destiny Voice)');
        $defaultCategoryInput->setValue($settingsObject->vc->defaultCategory);
        $defaultCategoryActionRow = ActionRow::new()->addComponent($defaultCategoryInput);

        $channelLimitInput = TextInput::new('Ліміт створення голосових каналів', TextInput::STYLE_SHORT, self::SETTINGS_VC_CHANNEL_LIMIT_INPUT)
            ->setPlaceholder('Від 1 до 10');
        $channelLimitInput->setValue($settingsObject->vc->channelLimit);
        $channelLimitActionRow = ActionRow::new()->addComponent($channelLimitInput);

        $interaction->showModal(
            'Налаштування команди /voicecreate',
            self::SETTINGS_VC_MODAL,
            [$defaultCategoryActionRow, $channelLimitActionRow],
            self::onModalSubmit($interaction)
        );
    }

    private static function onModalSubmit(Interaction $prevInteraction): callable
    {
        return function (Interaction $interaction, Collection $components) use ($prevInteraction) {
            $collection = new Collection();
            foreach ($interaction->data->components as $component) {
                $collection->set($component->components->first()->custom_id, $component->components->first()->value);
            }

            list($settingsObject, $settingsModel) = SettingsObject::getFromInteractionOrGetDefault($interaction, true);
            /** @var SettingsObject $settingsObject */
            $settingsObject->vc->defaultCategory = $collection[self::SETTINGS_VC_DEFAULT_CATEGORY_INPUT];
            $settingsObject->vc->channelLimit = (int)$collection[self::SETTINGS_VC_CHANNEL_LIMIT_INPUT];

            /** @var Setting $settingsModel object */
            $settingsModel->object = json_encode($settingsObject);
            $settingsModel->updated_by = $interaction->member->user->id;
            $settingsModel->save();

            /** @var Embed $newEmbed */
            $newEmbed = $prevInteraction->message->embeds->first();
            /** @var Field $field */
            $newEmbed->offsetUnset('fields');
            $newEmbed->addFieldValues('Ролі, яким надано доступ до використання команди', SlashCommandHelper::assembleAtRoleString(array_column($settingsObject->vc->permittedRoles, 'id')));
            $newEmbed->addFieldValues('Назва дефолтної підкатегорії', $settingsObject->vc->defaultCategory);
            $newEmbed->addFieldValues('Ліміт створення голосових каналів', $settingsObject->vc->channelLimit);

            $components = SlashCommandHelper::constructComponentsForMessageBuilderFromInteraction($prevInteraction);

            $prevInteraction->updateOriginalResponse(
                MessageBuilder::new()
                    ->setContent($prevInteraction->message->content)
                    ->addEmbed($newEmbed)
                    ->setComponents($components)
            );

            $interaction->acknowledge();
        };
    }
}
