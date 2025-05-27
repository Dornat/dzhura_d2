<?php

namespace App\Discord\SlashCommands\Settings\Factories;

use App\Discord\Helpers\SlashCommandHelper;
use App\Discord\SlashCommands\Settings\Objects\SettingsObject;
use App\Discord\SlashCommands\Settings\SelectMenuRoles;
use App\Setting;
use Discord\Builders\Components\Option;
use Discord\Builders\Components\SelectMenu;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Embed\Field;
use Discord\Parts\Interactions\Interaction;

class LfgSettingsFactory
{
    public const LFG_SETTINGS_IS_ROLES_TO_TAG_ACTIVE_SELECT = 'lfg_settings_is_roles_to_tag_active_select';
    public const LFG_SETTINGS_ROLES_TO_TAG_SELECT = 'lfg_settings_roles_to_tag_select';

    public Embed $embed;
    public SelectMenuRoles $roleSelect;
    public SelectMenu $activationSelect;

    public function __construct(Discord $discord, SettingsObject $settingsObject)
    {
        $this->embed = new Embed($discord);
        $this->embed->setColor('#34eb4c');
        $this->embed->setTitle('Налаштування команди /lfg');
        $this->embed->addFieldValues('Чи активований теггінг ролей', $settingsObject->lfg->isRolesToTagActive ? 'Активовано' : 'Деактивовано');
        $this->embed->addFieldValues('Список ролей для тих кого тегати після створення групи', SlashCommandHelper::assembleAtRoleString($settingsObject->lfg->rolesToTag));


        $this->activationSelect = SelectMenu::new(self::LFG_SETTINGS_IS_ROLES_TO_TAG_ACTIVE_SELECT)
            ->setPlaceholder('Активація теггінгу ролей');
        $this->activationSelect->addOption(new Option('Деактивовано', 0));
        $this->activationSelect->addOption(new Option('Активовано', 1));

        $this->roleSelect = SelectMenuRoles::new(self::LFG_SETTINGS_ROLES_TO_TAG_SELECT)
            ->setPlaceholder('Обрати ролі для тих, кого тегати')
            ->setMinValues(0)
            ->setMaxValues(25);
    }

    public static function actOnIsRolesToTagActiveSelect(Interaction $interaction, Discord $discord): void
    {
        list($settingsObject, $settingsModel) = SettingsObject::getFromInteractionOrGetDefault($interaction, true);
        $settingsObject->lfg->isRolesToTagActive = (bool)$interaction->data->values[0];

        /** @var Setting $settingsModel object */
        $settingsModel->object = json_encode($settingsObject);
        $settingsModel->updated_by = $interaction->member->user->id;
        $settingsModel->save();

        /** @var Embed $newEmbed */
        $newEmbed = $interaction->message->embeds->first();
        /** @var Field $field */
        $newEmbed->offsetUnset('fields');
        $newEmbed->addFieldValues('Чи активований теггінг ролей', $settingsObject->lfg->isRolesToTagActive ? 'Активовано' : 'Деактивовано');
        $newEmbed->addFieldValues('Список ролей для тих кого тегати після створення групи', SlashCommandHelper::assembleAtRoleString($settingsObject->lfg->rolesToTag));

        $interaction->updateMessage(
            MessageBuilder::new()
                ->setContent($interaction->message->content)
                ->addEmbed($newEmbed)
                ->setComponents(SlashCommandHelper::constructComponentsForMessageBuilderFromInteraction($interaction))
        );
    }

    public static function actOnRolesToTagSelect(Interaction $interaction, Discord $discord): void
    {
        list($settingsObject, $settingsModel) = SettingsObject::getFromInteractionOrGetDefault($interaction, true);
        $settingsObject->lfg->rolesToTag = [];

        foreach ($interaction->data->resolved->roles as $role) {
            $settingsObject->lfg->rolesToTag[] = $role->id;
        }

        /** @var Setting $settingsModel object */
        $settingsModel->object = json_encode($settingsObject);
        $settingsModel->updated_by = $interaction->member->user->id;
        $settingsModel->save();

        $newEmbed = $interaction->message->embeds->first();
        /** @var Field $field */
        $newEmbed->offsetUnset('fields');
        $newEmbed->addFieldValues('Чи активований теггінг ролей', $settingsObject->lfg->isRolesToTagActive ? 'Активовано' : 'Деактивовано');
        $newEmbed->addFieldValues('Список ролей для тих кого тегати після створення групи', SlashCommandHelper::assembleAtRoleString($settingsObject->lfg->rolesToTag));

        $interaction->updateMessage(
            MessageBuilder::new()
                ->setContent($interaction->message->content)
                ->addEmbed($newEmbed)
                ->setComponents(SlashCommandHelper::constructComponentsForMessageBuilderFromInteraction($interaction))
        );
    }
}
