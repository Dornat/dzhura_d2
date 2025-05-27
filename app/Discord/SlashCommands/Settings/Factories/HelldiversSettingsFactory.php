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

class HelldiversSettingsFactory
{
    public const SETTINGS_HD_VC_CATEGORY_INPUT = 'settings_hd_vc_category_input';
    public const SETTINGS_HD_VC_LIMIT_INPUT = 'settings_hd_vc_limit_input';
    public const SETTINGS_HD_VC_NAME_INPUT = 'settings_hd_vc_name_input';
    public const SETTINGS_HD_VC_EMPTY_TIMEOUT_INPUT = 'settings_hd_vc_empty_timeout_input';
    public const SETTINGS_HD_PERMITTED_ROLES_SELECT = 'settings_hd_permitted_roles_select';
    public const SETTINGS_HD_RACES_ROLES_SELECT = 'settings_hd_races_roles_select';
    public const SETTINGS_HD_LEVELS_ROLES_SELECT = 'settings_hd_levels_roles_select';
    public const SETTINGS_HD_OPEN_MODAL_BTN = 'settings_hd_open_modal_btn';
    public const SETTINGS_HD_MODAL = 'settings_hd_modal';

    public Embed $embed;
    public ActionRow $modalBtnActionRow;
    public SelectMenuRoles $permittedRolesSelect;
    public SelectMenuRoles $racesRolesSelect;
    public SelectMenuRoles $levelsRolesSelect;

    public function __construct(Discord $discord, SettingsObject $settingsObject)
    {
        $this->embed = new Embed($discord);
        $this->embed->setColor('#34eb4c');
        $this->embed->setTitle('Налаштування команди /helldivers lfg');
        $this->embed->addField(...self::assembleEmbedFields($settingsObject));

        $hdSettingsModalBtn = Button::new(Button::STYLE_PRIMARY)
            ->setLabel('Зміна налаштувань /helldivers')
            ->setCustomId(self::SETTINGS_HD_OPEN_MODAL_BTN);
        $this->modalBtnActionRow = ActionRow::new()->addComponent($hdSettingsModalBtn);

        $this->permittedRolesSelect = SelectMenuRoles::new(self::SETTINGS_HD_PERMITTED_ROLES_SELECT)
            ->setPlaceholder('Ролі, яким надано доступ до створення lfg')
            ->setMinValues(0)
            ->setMaxValues(25);

        $this->racesRolesSelect = SelectMenuRoles::new(self::SETTINGS_HD_RACES_ROLES_SELECT)
            ->setPlaceholder('Ролі рас, які можна буде тегати')
            ->setMinValues(0)
            ->setMaxValues(25);

        $this->levelsRolesSelect = SelectMenuRoles::new(self::SETTINGS_HD_LEVELS_ROLES_SELECT)
            ->setPlaceholder('Ролі рівнів, які можна буде тегати')
            ->setMinValues(0)
            ->setMaxValues(25);
    }

    public static function actOnHelldiversSettingsModalOpenBtn(Interaction $interaction, Discord $discord): void
    {
        $settingsObject = SettingsObject::getFromInteractionOrGetDefault($interaction);

        $vcCategoryInput = TextInput::new('Назва підкатегорії для голосові канали', TextInput::STYLE_SHORT, self::SETTINGS_HD_VC_CATEGORY_INPUT)
            ->setPlaceholder('Коротка назва (напр. Helldivers Groups)');
        $vcCategoryInput->setValue($settingsObject->helldivers->vcCategory);
        $vcCategoryActionRow = ActionRow::new()->addComponent($vcCategoryInput);

        $vcLimitInput = TextInput::new('Ліміт створення голосових каналів', TextInput::STYLE_SHORT, self::SETTINGS_HD_VC_LIMIT_INPUT)
            ->setPlaceholder('Від 1 до 10');
        $vcLimitInput->setValue($settingsObject->helldivers->vcLimit);
        $vcLimitActionRow = ActionRow::new()->addComponent($vcLimitInput);

        $vcNameInput = TextInput::new('Назва голосових каналів для lfg', TextInput::STYLE_SHORT, self::SETTINGS_HD_VC_NAME_INPUT)
            ->setPlaceholder('Текст і використання змінної {player} (напр. ={player}=)');
        $vcNameInput->setValue($settingsObject->helldivers->vcName);
        $vcNameActionRow = ActionRow::new()->addComponent($vcNameInput);

        $emptyVcTimeout = TextInput::new('Час видалення неактивного голосового каналу', TextInput::STYLE_SHORT, self::SETTINGS_HD_VC_EMPTY_TIMEOUT_INPUT)
            ->setPlaceholder('Число, час в хвилинах');
        $emptyVcTimeout->setValue($settingsObject->helldivers->emptyVcTimeout / 60);
        $emptyVcTimeoutActionRow = ActionRow::new()->addComponent($emptyVcTimeout);

        $interaction->showModal(
            'Налаштування команди /helldivers',
            self::SETTINGS_HD_MODAL,
            [$vcCategoryActionRow, $vcLimitActionRow, $vcNameActionRow, $emptyVcTimeoutActionRow],
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
            $settingsObject->helldivers->vcCategory = $collection[self::SETTINGS_HD_VC_CATEGORY_INPUT];
            $settingsObject->helldivers->vcLimit = (int)$collection[self::SETTINGS_HD_VC_LIMIT_INPUT];
            $settingsObject->helldivers->vcName = $collection[self::SETTINGS_HD_VC_NAME_INPUT];
            $settingsObject->helldivers->emptyVcTimeout = (int)$collection[self::SETTINGS_HD_VC_EMPTY_TIMEOUT_INPUT] * 60;

            /** @var Setting $settingsModel object */
            $settingsModel->object = json_encode($settingsObject);
            $settingsModel->updated_by = $interaction->member->user->id;
            $settingsModel->save();

            /** @var Embed $newEmbed */
            $newEmbed = $prevInteraction->message->embeds->first();
            /** @var Field $field */
            $newEmbed->offsetUnset('fields');
            $newEmbed->addField(...self::assembleEmbedFields($settingsObject));

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

    public static function actOnPermittedRolesSelect(Interaction $interaction, Discord $discord): void
    {
        list($settingsObject, $settingsModel) = SettingsObject::getFromInteractionOrGetDefault($interaction, true);
        $settingsObject->helldivers->permittedRoles = [];

        foreach ($interaction->data->resolved->roles as $role) {
            $settingsObject->helldivers->permittedRoles[] = [
                'id' => $role->id,
                'name' => $role->name
            ];
        }

        self::updateSettingsModelAndEmbed($settingsObject, $settingsModel, $interaction);
    }

    public static function actOnRacesRolesSelect(Interaction $interaction, Discord $discord): void
    {
        list($settingsObject, $settingsModel) = SettingsObject::getFromInteractionOrGetDefault($interaction, true);
        $settingsObject->helldivers->racesRoles = [];

        foreach ($interaction->data->resolved->roles as $role) {
            $settingsObject->helldivers->racesRoles[] = [
                'id' => $role->id,
                'name' => $role->name
            ];
        }

        self::updateSettingsModelAndEmbed($settingsObject, $settingsModel, $interaction);
    }

    public static function actOnLevelsRolesSelect(Interaction $interaction, Discord $discord): void
    {
        list($settingsObject, $settingsModel) = SettingsObject::getFromInteractionOrGetDefault($interaction, true);
        $settingsObject->helldivers->levelsRoles = [];

        foreach ($interaction->data->resolved->roles as $role) {
            $settingsObject->helldivers->levelsRoles[] = [
                'id' => $role->id,
                'name' => $role->name
            ];
        }

        self::updateSettingsModelAndEmbed($settingsObject, $settingsModel, $interaction);
    }

    private static function assembleEmbedFields(SettingsObject $settingsObject): array
    {
        return [
            [
                'name' => 'Назва підкатегорії для госових каналів',
                'value' => $settingsObject->helldivers->vcCategory,
                'inline' => false,
            ],
            [
                'name' => 'Ліміт створення голосових каналів',
                'value' => $settingsObject->helldivers->vcLimit,
                'inline' => false,
            ],
            [
                'name' => 'Ім\'я голосового каналу після створення користувачем',
                'value' => self::wrapVariablesIntoBackticks($settingsObject->helldivers->vcName),
                'inline' => false,
            ],
            [
                'name' => 'Час після якого неактивний голосовий канал буде видалено',
                'value' => self::emptyVcMinuteMessageTranspiler($settingsObject->helldivers->emptyVcTimeout / 60),
                'inline' => false,
            ],
            [
                'name' => 'Ролі, яким надано доступ до створення lfg',
                'value' => SlashCommandHelper::assembleAtRoleString(array_column($settingsObject->helldivers->permittedRoles, 'id')),
                'inline' => false,
            ],
            [
                'name' => 'Ролі рас, які можна буде тегати',
                'value' => SlashCommandHelper::assembleAtRoleString(array_column($settingsObject->helldivers->racesRoles, 'id')),
                'inline' => false,
            ],
            [
                'name' => 'Ролі рівнів, які можна буде тегати',
                'value' => SlashCommandHelper::assembleAtRoleString(array_column($settingsObject->helldivers->levelsRoles, 'id')),
                'inline' => false,
            ],
        ];
    }

    private static function emptyVcMinuteMessageTranspiler(int $emptyVcTimeoutInMinutes): string
    {
        $forms = ['хвилина', 'хвилини', 'хвилин'];
        $mod100 = $emptyVcTimeoutInMinutes % 100;

        if ($mod100 > 4 && $mod100 < 20) {
            return "$emptyVcTimeoutInMinutes $forms[2]";
        }

        $mod10 = $emptyVcTimeoutInMinutes % 10;
        if ($mod10 === 1) {
            return "$emptyVcTimeoutInMinutes $forms[0]";
        }

        if ($mod10 >= 2 && $mod10 <= 4) {
            return "$emptyVcTimeoutInMinutes $forms[1]";
        }

        return "$emptyVcTimeoutInMinutes $forms[2]";
    }

    private static function wrapVariablesIntoBackticks(string $string): string
    {
        return str_replace(
            ['{player}'],
            ['`{player}`'],
            $string
        );
    }

    public static function updateSettingsModelAndEmbed(SettingsObject $settingsObject, Setting $settingsModel, Interaction $interaction): void
    {
        $settingsModel->object = json_encode($settingsObject);
        $settingsModel->updated_by = $interaction->member->user->id;
        $settingsModel->save();

        /** @var Embed $newEmbed */
        $newEmbed = $interaction->message->embeds->first();
        /** @var Field $field */
        $newEmbed->offsetUnset('fields');
        $newEmbed->addField(...self::assembleEmbedFields($settingsObject));

        $interaction->updateMessage(
            MessageBuilder::new()
                ->setContent($interaction->message->content)
                ->addEmbed($newEmbed)
                ->setComponents(SlashCommandHelper::constructComponentsForMessageBuilderFromInteraction($interaction))
        );
    }
}
