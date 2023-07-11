<?php

namespace App\Discord\SlashCommands\Settings\Factories;

use App\Discord\Helpers\SlashCommandHelper;
use App\Discord\SlashCommands\Settings\Objects\Levels\AnnouncementChannelEnum;
use App\Discord\SlashCommands\Settings\Objects\Levels\CustomChannelObject;
use App\Discord\SlashCommands\Settings\Objects\Levels\RoleRewardsTypeEnum;
use App\Discord\SlashCommands\Settings\Objects\SettingsObject;
use App\Discord\SlashCommands\Settings\SelectMenuChannels;
use App\Discord\SlashCommands\Settings\SelectMenuRoles;
use App\Setting;
use Discord\Builders\Components\ActionRow;
use Discord\Builders\Components\Button;
use Discord\Builders\Components\Option;
use Discord\Builders\Components\SelectMenu;
use Discord\Builders\Components\TextInput;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Helpers\Collection;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Embed\Field;
use Discord\Parts\Interactions\Interaction;
use Discord\WebSockets\Op;

class LevelsSettingsFactory
{
    public const ACTIVATE = 'activate';
    public const LEVEL_UP_ANNOUNCEMENT = 'level-up-announcement';
    public const ROLE_REWARDS = 'role-rewards';

    public const ACTIVATE_SELECT = 'activate_select';
    public const ANNOUNCEMENT_CHANNEL_SELECT = 'announcement_channel_select';
    public const CUSTOM_CHANNEL_SELECT = 'custom_channel_select';
    public const CUSTOMIZE_ANNOUNCEMENT_MESSAGE_BTN = 'customize_announcement_message_btn';
    public const CUSTOMIZE_ANNOUNCEMENT_MESSAGE_INPUT = 'customize_announcement_message_input';
    public const CUSTOMIZE_ANNOUNCEMENT_MESSAGE_MODAL = 'customize_announcement_message_modal';
    public const ROLE_REWARDS_TYPE_SELECT = 'role_rewards_type_select';
    public const REMOVE_ROLE_REWARDS_ON_DEMOTION_SELECT = 'remove_role_rewards_on_demotion_select';
    public const ROLE_REWARDS_LEVEL_NUMBER_SELECT = 'role_rewards_level_number_select';
    public const ROLE_REWARDS_LEVEL_ROLE_SELECT = 'role_rewards_level_role_select';
    public const ROLE_REWARDS_BTN_CLEAR = 'role_rewards_btn_clear';

    public static function actOnActivateCommand(Interaction $interaction, Discord $discord, SettingsObject $settingsObject): void
    {
        $embed = new Embed($discord);
        $embed->setColor('#024ad9');
        $embed->setTitle('Система левелінгу');
        $embed->addFieldValues('Стан', $settingsObject->levels->active ? 'Активовано' : 'Деактивовано');

        $activationSelect = SelectMenu::new(self::ACTIVATE_SELECT)
            ->setPlaceholder('Активація системи левелінгу');
        $activationSelect->addOption(new Option('Деактивовано', false));
        $activationSelect->addOption(new Option('Активовано', true));

        $msg = MessageBuilder::new()
            ->setContent("> 📖 За замовчуванням система левелінгу вимкнена. Тут можна її активувати.\n> \n> ⚙")
            ->setEmbeds([$embed])
            ->setComponents([
                $activationSelect
            ]);

        $interaction->respondWithMessage($msg, true);
    }

    public static function actOnActivateSelect(Interaction $interaction, Discord $discord): void
    {
        list($settingsObject, $settingsModel) = SettingsObject::getFromInteractionOrGetDefault($interaction, true);
        /** @var SettingsObject $settingsObject */
        $settingsObject->levels->active = (bool)$interaction->data->values[0];

        /** @var Setting $settingsModel object */
        $settingsModel->object = json_encode($settingsObject);
        $settingsModel->updated_by = $interaction->member->user->id;
        $settingsModel->save();

        $newEmbed = $interaction->message->embeds->first();
        /** @var Field $field */
        $field = $newEmbed->fields->first();
        $newEmbed->offsetUnset('fields');
        $field->offsetSet('value', $settingsObject->levels->active ? 'Активовано' : 'Деактивовано');
        $newEmbed->addField($field);

        $interaction->updateMessage(
            MessageBuilder::new()
                ->setContent($interaction->message->content)
                ->addEmbed($newEmbed)
                ->setComponents(SlashCommandHelper::constructComponentsForMessageBuilderFromInteraction($interaction))
        );
    }

    public static function actOnLevelUpAnnouncementCommand(Interaction $interaction, Discord $discord, SettingsObject $settingsObject): void
    {
        $embed = new Embed($discord);
        $embed->setColor('#024ad9');
        $embed->setTitle('Оголошення про досягнення нового рівня');
        $embed->addFieldValues('Канал, куди йтимуть оголошення', $settingsObject->levels->levelUpAnnouncement->channel->label());

        $components = [];

        $components[] = SelectMenu::new(self::ANNOUNCEMENT_CHANNEL_SELECT)
            ->setPlaceholder('Канал куди йтимуть оголошення')
            ->addOption(new Option(AnnouncementChannelEnum::DISABLED->label(), AnnouncementChannelEnum::DISABLED->value))
            ->addOption(new Option(AnnouncementChannelEnum::CURRENT_CHANNEL->label(), AnnouncementChannelEnum::CURRENT_CHANNEL->value))
            ->addOption(new Option(AnnouncementChannelEnum::PRIVATE_MESSAGE->label(), AnnouncementChannelEnum::PRIVATE_MESSAGE->value))
            ->addOption(new Option(AnnouncementChannelEnum::CUSTOM_CHANNEL->label(), AnnouncementChannelEnum::CUSTOM_CHANNEL->value));

        if ($settingsObject->levels->levelUpAnnouncement->channel === AnnouncementChannelEnum::CUSTOM_CHANNEL) {
            $embed->addFieldValues('Канал на сервері', isset($settingsObject->levels->levelUpAnnouncement->customChannel) ? '#' . $settingsObject->levels->levelUpAnnouncement->customChannel->name : 'Не обрано');

            $components[] = SelectMenuChannels::new(self::CUSTOM_CHANNEL_SELECT)
                ->setChannelTypes([SelectMenuChannels::GUILD_TEXT_CHANNEL_TYPE])
                ->setPlaceholder('Вказати власний канал')
                ->setMinValues(0)
                ->setMaxValues(1);
        }

        $embed->addFieldValues('Повідомлення', self::wrapVariablesIntoBackticks($settingsObject->levels->levelUpAnnouncement->announcementMessage));

        $components[] = ActionRow::new()->addComponent(
            Button::new(Button::STYLE_PRIMARY)
                ->setLabel('Змінити повідомлення')
                ->setCustomId(self::CUSTOMIZE_ANNOUNCEMENT_MESSAGE_BTN)
        );

        $msg = MessageBuilder::new()
            ->setContent("> 📖 Коли користувач досягає нового рівня Джура може відправити кастомізоване повідомлення.\n> \n> ⚙")
            ->setEmbeds([$embed])
            ->setComponents($components);

        $interaction->respondWithMessage($msg, true);
    }

    public static function actOnAnnouncementChannelSelect(Interaction $interaction, Discord $discord): void
    {
        list($settingsObject, $settingsModel) = SettingsObject::getFromInteractionOrGetDefault($interaction, true);
        /** @var SettingsObject $settingsObject */
        $settingsObject->levels->levelUpAnnouncement->channel = AnnouncementChannelEnum::tryFrom($interaction->data->values[0]);

        /** @var Setting $settingsModel object */
        $settingsModel->object = json_encode($settingsObject);
        $settingsModel->updated_by = $interaction->member->user->id;
        $settingsModel->save();

        $newEmbed = $interaction->message->embeds->first();
        /** @var Field $field */
        $field = $newEmbed->fields->first();
        $newEmbed->offsetUnset('fields');
        $field->offsetSet('value', $settingsObject->levels->levelUpAnnouncement->channel->label());
        $newEmbed->addField($field);

        $components = SlashCommandHelper::constructComponentsForMessageBuilderFromInteraction($interaction);

        if ($settingsObject->levels->levelUpAnnouncement->channel === AnnouncementChannelEnum::CUSTOM_CHANNEL) {
            $newEmbed->addFieldValues('Канал на сервері', isset($settingsObject->levels->levelUpAnnouncement->customChannel) ? '#' . $settingsObject->levels->levelUpAnnouncement->customChannel->name : 'Не обрано');
            array_splice(
                $components,
                1,
                0,
                [SelectMenuChannels::new(self::CUSTOM_CHANNEL_SELECT)
                    ->setChannelTypes([SelectMenuChannels::GUILD_TEXT_CHANNEL_TYPE])
                    ->setPlaceholder('Вказати власний канал')
                    ->setMinValues(0)
                    ->setMaxValues(1)]
            );
        } else {
            $components = array_filter($components, function ($component) {
                if (!($component instanceof ActionRow) && $component->getCustomId() === self::CUSTOM_CHANNEL_SELECT) {
                    return false;
                }
                return true;
            });
        }

        $newEmbed->addFieldValues('Повідомлення', self::wrapVariablesIntoBackticks($settingsObject->levels->levelUpAnnouncement->announcementMessage));

        $interaction->updateMessage(
            MessageBuilder::new()
                ->setContent($interaction->message->content)
                ->addEmbed($newEmbed)
                ->setComponents($components)
        );
    }

    public static function actOnCustomChannelSelect(Interaction $interaction, Discord $discord): void
    {
        list($settingsObject, $settingsModel) = SettingsObject::getFromInteractionOrGetDefault($interaction, true);
        $selectedChannel = $interaction->data->resolved?->channels->first();
        if (is_null($selectedChannel)) {
            $interaction->acknowledge();
            return;
        }

        /** @var SettingsObject $settingsObject */
        $settingsObject->levels->levelUpAnnouncement->customChannel = new CustomChannelObject([
            'id' => $selectedChannel->id,
            'name' => $selectedChannel->name
        ]);

        /** @var Setting $settingsModel object */
        $settingsModel->object = json_encode($settingsObject);
        $settingsModel->updated_by = $interaction->member->user->id;
        $settingsModel->save();

        $newEmbed = $interaction->message->embeds->first();
        $newEmbed->offsetUnset('fields');
        $newEmbed->addFieldValues('Канал, куди йтимуть оголошення', $settingsObject->levels->levelUpAnnouncement->channel->label());
        $newEmbed->addFieldValues('Канал на сервері', is_null($settingsObject->levels->levelUpAnnouncement->customChannel) ? '' : '#' . $settingsObject->levels->levelUpAnnouncement->customChannel->name);
        $newEmbed->addFieldValues('Повідомлення', self::wrapVariablesIntoBackticks($settingsObject->levels->levelUpAnnouncement->announcementMessage));

        $components = SlashCommandHelper::constructComponentsForMessageBuilderFromInteraction($interaction);

        $interaction->updateMessage(
            MessageBuilder::new()
                ->setContent($interaction->message->content)
                ->addEmbed($newEmbed)
                ->setComponents($components)
        );
    }

    public static function actOnCustomizeAnnouncementMessageBtn(Interaction $interaction, Discord $discord): void
    {
        $settingsObject = SettingsObject::getFromInteractionOrGetDefault($interaction);

        $actionRow = ActionRow::new()
            ->addComponent(
                TextInput::new('Повідомлення', TextInput::STYLE_PARAGRAPH, self::CUSTOMIZE_ANNOUNCEMENT_MESSAGE_INPUT)
                    ->setValue($settingsObject->levels->levelUpAnnouncement->announcementMessage)
                    ->setRequired(true)
            );

        $interaction->showModal(
            'Редагування повідомлення',
            self::CUSTOMIZE_ANNOUNCEMENT_MESSAGE_MODAL,
            [$actionRow],
            self::onModalSubmit($interaction)
        );
    }

    private static function onModalSubmit(Interaction $prevInteraction): callable
    {
        return function (Interaction $interaction, Collection $components) use ($prevInteraction) {
            list($settingsObject, $settingsModel) = SettingsObject::getFromInteractionOrGetDefault($interaction, true);

            /** @var SettingsObject $settingsObject */
            $settingsObject->levels->levelUpAnnouncement->announcementMessage = $components[self::CUSTOMIZE_ANNOUNCEMENT_MESSAGE_INPUT]->value;

            /** @var Setting $settingsModel object */
            $settingsModel->object = json_encode($settingsObject);
            $settingsModel->updated_by = $interaction->member->user->id;
            $settingsModel->save();

            $newEmbed = $prevInteraction->message->embeds->first();
            $newEmbed->offsetUnset('fields');
            $newEmbed->addFieldValues('Канал, куди йтимуть оголошення', $settingsObject->levels->levelUpAnnouncement->channel->label());
            if ($settingsObject->levels->levelUpAnnouncement->channel === AnnouncementChannelEnum::CUSTOM_CHANNEL) {
                $newEmbed->addFieldValues('Канал на сервері', isset($settingsObject->levels->levelUpAnnouncement->customChannel) ? '#' . $settingsObject->levels->levelUpAnnouncement->customChannel->name : 'Не обрано');
            }
            $newEmbed->addFieldValues('Повідомлення', self::wrapVariablesIntoBackticks($settingsObject->levels->levelUpAnnouncement->announcementMessage));

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

    private static function wrapVariablesIntoBackticks(string $string): string
    {
        return str_replace(
            ['{player}', '{level}'],
            ['`{player}`', '`{level}`'],
            $string
        );
    }

    public static function actOnRoleRewardsCommand(Interaction $interaction, Discord $discord, SettingsObject $settingsObject): void
    {
        $embed = new Embed($discord);
        $embed->setColor('#024ad9');
        $embed->setTitle('Налаштування рольових винагород');
        $embed->addFieldValues('Тип додавання ролей', $settingsObject->levels->roleRewards->roleRewardsType->label());
        $embed->addFieldValues('Прибирати ролі при зменшенні рівня (внаслідок команди /levels remove-xp)', $settingsObject->levels->roleRewards->removeRoleRewardsOnDemotion ? 'Так' : 'Ні');
        $embed->addFieldValues('Рівневі винагороди', $settingsObject->levels->roleRewards->roleRewardsToString());

        $components = [];

        $components[] = SelectMenu::new(self::ROLE_REWARDS_TYPE_SELECT)
            ->setPlaceholder('Тип додавання ролей')
            ->addOption(new Option(RoleRewardsTypeEnum::STACK_PREVIOUS_REWARDS->label(), RoleRewardsTypeEnum::STACK_PREVIOUS_REWARDS->value))
            ->addOption(new Option(RoleRewardsTypeEnum::REMOVE_PREVIOUS_REWARDS->label(), RoleRewardsTypeEnum::REMOVE_PREVIOUS_REWARDS->value));

        $components[] = SelectMenu::new(self::REMOVE_ROLE_REWARDS_ON_DEMOTION_SELECT)
            ->setPlaceholder('Прибирати ролі при зменшенні рівня')
            ->addOption(new Option('Ні', false))
            ->addOption(new Option('Так', true));

        $levelNumberSelect = SelectMenu::new(self::ROLE_REWARDS_LEVEL_NUMBER_SELECT)
            ->setPlaceholder('Рівень');
        for ($i = 1; $i < 26; $i++) {
            $levelNumberSelect->addOption(new Option($i, $i));
        }
        $components[] = $levelNumberSelect;

        $components[] = SelectMenuRoles::new(self::ROLE_REWARDS_LEVEL_ROLE_SELECT)
            ->setPlaceholder('Роль')
            ->setMinValues(0)
            ->setMaxValues(1);

        $btnActionRow = ActionRow::new();
        $btnActionRow->addComponent(Button::new(Button::STYLE_DANGER, self::ROLE_REWARDS_BTN_CLEAR)->setLabel('Очистити всі рівневі винагороди'));

        $components[] = $btnActionRow;

        $msg = MessageBuilder::new()
            ->setContent("> 📖 Коли користувач досягає нового рівня Джура може призначити якусь роль в залежності від досягнутого рівня. Тут можна обрати видаляти чи додавати нові ролі за досягнення певних рівнів чи ні. Тобто, по досягненню певного рівня, за який дають нову роль, поперендьо досягнута роль буде прибиратися або ж вона буде додавадись до вже досягнутої.\n> \n> Процес додавання ролей за рівень такий: обрати рівень в селекторі \"Рівень\", потім обрати потрібну роль в селекторі \"Роль\" під рівнем. Щоби змінити вже встановлений рівень з роллю, достатньо просто обрати потрібний рівень і у відповідному полі змінити роль на нову. Через певні обмеження дискорду видаляти додані ролі окремо немає можливості, тому для видалення ролей потрібно натиснути на кнопку \"Очистити всі рівневі винагороди\" і всі налаштування, що пов'язані з присвоєнням ролей за рівні зітруться і доведеться налаштовувати знову.\n> \n> ⚙")
            ->setEmbeds([$embed])
            ->setComponents($components);

        $interaction->respondWithMessage($msg, true);
    }

    public static function actOnRoleRewardsTypeSelect(Interaction $interaction, Discord $discord): void
    {
        /** @var SettingsObject $settingsObject */
        list($settingsObject, $settingsModel) = SettingsObject::getFromInteractionOrGetDefault($interaction, true);
        $settingsObject->levels->roleRewards->roleRewardsType = RoleRewardsTypeEnum::tryFrom($interaction->data->values[0]);

        /** @var Setting $settingsModel object */
        $settingsModel->object = json_encode($settingsObject);
        $settingsModel->updated_by = $interaction->member->user->id;
        $settingsModel->save();

        $newEmbed = $interaction->message->embeds->first();
        $newEmbed->offsetUnset('fields');
        $newEmbed->addFieldValues('Тип додавання ролей', $settingsObject->levels->roleRewards->roleRewardsType->label());
        $newEmbed->addFieldValues('Прибирати ролі при зменшенні рівня (внаслідок команди /levels remove-xp)', $settingsObject->levels->roleRewards->removeRoleRewardsOnDemotion ? 'Так' : 'Ні');
        $newEmbed->addFieldValues('Рівневі винагороди', $settingsObject->levels->roleRewards->roleRewardsToString());

        $components = SlashCommandHelper::constructComponentsForMessageBuilderFromInteraction($interaction);

        $interaction->updateMessage(
            MessageBuilder::new()
                ->setContent($interaction->message->content)
                ->addEmbed($newEmbed)
                ->setComponents($components)
        );

        $interaction->acknowledge();
    }

    public static function actOnRemoveRoleRewardsOnDemotionSelect(Interaction $interaction, Discord $discord): void
    {
        /** @var SettingsObject $settingsObject */
        list($settingsObject, $settingsModel) = SettingsObject::getFromInteractionOrGetDefault($interaction, true);
        $settingsObject->levels->roleRewards->removeRoleRewardsOnDemotion = (bool)$interaction->data->values[0];

        /** @var Setting $settingsModel object */
        $settingsModel->object = json_encode($settingsObject);
        $settingsModel->updated_by = $interaction->member->user->id;
        $settingsModel->save();

        $newEmbed = $interaction->message->embeds->first();
        $newEmbed->offsetUnset('fields');
        $newEmbed->addFieldValues('Тип додавання ролей', $settingsObject->levels->roleRewards->roleRewardsType->label());
        $newEmbed->addFieldValues('Прибирати ролі при зменшенні рівня (внаслідок команди /levels remove-xp)', $settingsObject->levels->roleRewards->removeRoleRewardsOnDemotion ? 'Так' : 'Ні');
        $newEmbed->addFieldValues('Рівневі винагороди', $settingsObject->levels->roleRewards->roleRewardsToString());

        $components = SlashCommandHelper::constructComponentsForMessageBuilderFromInteraction($interaction);

        $interaction->updateMessage(
            MessageBuilder::new()
                ->setContent($interaction->message->content)
                ->addEmbed($newEmbed)
                ->setComponents($components)
        );

        $interaction->acknowledge();
    }

    public static function actOnRoleRewardsLevelNumberSelect(Interaction $interaction, Discord $discord): void
    {
        /** @var Embed $newEmbed */
        $newEmbed = $interaction->message->embeds->first();

        $newEmbed->setFooter($interaction->data->values[0]);

        $components = SlashCommandHelper::constructComponentsForMessageBuilderFromInteraction($interaction);
        $interaction->updateMessage(
            MessageBuilder::new()
                ->setContent($interaction->message->content)
                ->addEmbed($newEmbed)
                ->setComponents($components)
        );
    }

    public static function actOnRoleRewardsLevelRoleSelect(Interaction $interaction, Discord $discord): void
    {
        /** @var Embed $newEmbed */
        $newEmbed = $interaction->message->embeds->first();

        $footerText = $newEmbed->footer?->text;
        $role = $interaction->data?->values[0] ?? null;

        if (empty($footerText)) {
            if (empty($role)) {
                $interaction->acknowledge();
                return;
            }
            $interaction->respondWithMessage(MessageBuilder::new()->setContent('Спершу обери рівень.'), true);
            return;
        }

        $components = SlashCommandHelper::constructComponentsForMessageBuilderFromInteraction($interaction);
        $newEmbed->setFooter('');

        if (empty($role)) {
            $interaction->updateMessage(
                MessageBuilder::new()
                    ->setContent($interaction->message->content)
                    ->addEmbed($newEmbed)
                    ->setComponents($components)
            );
            return;
        }

        /** @var SettingsObject $settingsObject */
        list($settingsObject, $settingsModel) = SettingsObject::getFromInteractionOrGetDefault($interaction, true);
        $settingsObject->levels->roleRewards->roleRewards[$footerText] = $role;

        /** @var Setting $settingsModel object */
        $settingsModel->object = json_encode($settingsObject);
        $settingsModel->updated_by = $interaction->member->user->id;
        $settingsModel->save();

        $newEmbed->offsetUnset('fields');
        $newEmbed->addFieldValues('Тип додавання ролей', $settingsObject->levels->roleRewards->roleRewardsType->label());
        $newEmbed->addFieldValues('Прибирати ролі при зменшенні рівня (внаслідок команди /levels remove-xp)', $settingsObject->levels->roleRewards->removeRoleRewardsOnDemotion ? 'Так' : 'Ні');
        $newEmbed->addFieldValues('Рівневі винагороди', $settingsObject->levels->roleRewards->roleRewardsToString());

        $interaction->updateMessage(
            MessageBuilder::new()
                ->setContent($interaction->message->content)
                ->addEmbed($newEmbed)
                ->setComponents($components)
        );
    }

    public static function actOnRoleRewardsBtnClear(Interaction $interaction, Discord $discord): void
    {
        $components = SlashCommandHelper::constructComponentsForMessageBuilderFromInteraction($interaction);
        /** @var Embed $newEmbed */
        $newEmbed = $interaction->message->embeds->first();
        $newEmbed->setFooter('');

        /** @var SettingsObject $settingsObject */
        list($settingsObject, $settingsModel) = SettingsObject::getFromInteractionOrGetDefault($interaction, true);
        $settingsObject->levels->roleRewards->roleRewards = [];

        /** @var Setting $settingsModel object */
        $settingsModel->object = json_encode($settingsObject);
        $settingsModel->updated_by = $interaction->member->user->id;
        $settingsModel->save();

        $newEmbed->offsetUnset('fields');
        $newEmbed->addFieldValues('Тип додавання ролей', $settingsObject->levels->roleRewards->roleRewardsType->label());
        $newEmbed->addFieldValues('Прибирати ролі при зменшенні рівня (внаслідок команди /levels remove-xp)', $settingsObject->levels->roleRewards->removeRoleRewardsOnDemotion ? 'Так' : 'Ні');
        $newEmbed->addFieldValues('Рівневі винагороди', $settingsObject->levels->roleRewards->roleRewardsToString());

        $interaction->updateMessage(
            MessageBuilder::new()
                ->setContent($interaction->message->content)
                ->addEmbed($newEmbed)
                ->setComponents($components)
        );
    }
}