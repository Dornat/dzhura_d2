<?php

namespace App\Discord\SlashCommands;

use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Http\Exceptions\NoPermissionsException;
use Discord\InteractionType;
use Discord\Parts\Channel\Message;
use Discord\Parts\Interactions\Interaction;

class ZavalaSlashCommand implements SlashCommandListenerInterface
{
    public const ZAVALA = 'zavala';

    public static function act(Interaction $interaction, Discord $discord): void
    {
        if (!($interaction->type === InteractionType::APPLICATION_COMMAND && $interaction->data->name === self::ZAVALA)) {
            return;
        }

        try {
            $interaction->channel->sendMessage(
                MessageBuilder::new()
                    ->setContent('**' . self::phrases()[array_rand(self::phrases())] . "**\n\n © Командир Завала")
            )->done(function (Message $message) use ($interaction) {
            }, function (Message $message) use ($interaction) {
            });
        } catch (NoPermissionsException $e) {
        }

        $interaction->respondWithMessage(MessageBuilder::new()->setContent('🗿'))->done(function () use ($interaction) {
            $interaction->deleteOriginalResponse();
        });
    }

    private static function phrases(): array
    {
        return [
            "Стражі, об'єднуйтесь! Темрява чекає, і ми переможемо!",
            "Обіймай Світло, бо це наша найвеличніша зброя проти темряви.",
            "Чи в Колізеї, чи на глибині рейдів, ми боремося як одне ціле, об'єднані нашим світлом.",
            "У протистоянні проти складнощів ми піднімаємося. Наша доля створюється нашими діями.",
            "З попелу Краху ми вийшли сильнішими, готовими протистояти будь-якому виклику.",
            "Розкрий свою силу, тримай зброю та стань героєм, якого потребує Останнє Місто.",
            "Від Вежі до віддалених планет, наше завдання зрозуміле: захищати людство за будь-яку ціну.",
            "Прийми виклик, Стражу. Наші дії сьогодні визначать майбутнє людства.",
            "Через командну роботу та жертву ми подолаємо будь-яку перешкоду на своєму шляху.",
            "Готуйся до бою, Стражу. Вороги людства не здогадуються, що їх чекає.",
            "Навіть у найтемнішій темряві наше Світло сяє яскравіше. Обійми його, Стражу.",
            "Світло надії розвіює темряву, випромінюючи міць нашого буття.",
            "Темрява намагається поглинути світло, але ми вистоїмо, щоб його захистити.",
            "Усередині нас живе вічне полум'я Світла, що ніколи не згасне.",
            "Закликаємо Світло стати нашим щитом, коли темні сили намагаються підкорити нас.",
            "Світло дає нам силу пройти крізь морок та знайти наше справжнє покликання.",
            "Там, де немає світла, ми стаємо його джерелом. Нехай його блиск відбивається в кожному ділі.",
            "Ми народилися в світлі, щоб захищати його. Темрява не має шансу переважити.",
            "Вогонь Світла зігріває наші серця та розсіює морок, навіть найглибший.",
            "Темрява може викликати страх, але наше Світло дає нам мужність протистояти їй.",
            "У світлі ми знаходимо сміливість протистояти темряві, бо знаємо, що правда завжди перемагає.",
            "Світло є нашою найбільшою зброєю, що вбирає в себе всю міць і силу Всесвіту.",
            "Світло Стража несе з собою надію на відродження і перемогу над темрявою.",
            "Наша місія - боротися з темрявою, впроваджуючи Світло у кожний куточок Всесвіту."
        ];
    }
}