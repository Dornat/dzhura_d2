<?php

namespace App\Commands;

use App\Discord\SlashCommands\LfgDeleteSlashCommandListener;
use App\Discord\SlashCommands\LfgEditSlashCommand;
use App\Discord\SlashCommands\LfgSlashCommandListener;
use App\Discord\SlashCommands\VoiceChannelCreateSlashCommand;
use App\Discord\SlashCommands\VoiceChannelDeleteSlashCommand;
use Discord\Parts\Interactions\Command\Command as DiscordCommand;
use Discord\Discord;
use Discord\Exceptions\IntentException;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\User\Activity;
use Discord\WebSockets\Event;
use Exception;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Socket\SocketServer;

class Run extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'run';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'The main command that runs the bot.';

    /**
     * Execute the console command.
     *
     * @return void
     * @throws IntentException
     * @throws Exception
     */
    public function handle(): void
    {
        $discord = new Discord([
            'token' => env('DISCORD_TOKEN'),
        ]);

        $discord->on('ready', function (Discord $discord) {
            $botActivity = new Activity($discord, [
                'type' => Activity::TYPE_PLAYING,
                'name' => 'Ліквідація москалів. [v' . config('app.version') . ']'
            ]);
            $discord->updatePresence($botActivity);

            foreach (config('slash_commands') as $opts) {
                $slashCommand = new DiscordCommand($discord, $opts);
                $discord->application->commands->save($slashCommand);
            }
//
//            $http = new HttpServer(function (ServerRequestInterface $request) {
//                return Response::plaintext(
//                    "Hello World!\n"
//                );
//            });
//
//            $socket = new SocketServer('127.0.0.1:8080');
//            $http->listen($socket);
        });

        $discord->on(Event::INTERACTION_CREATE, function (Interaction $interaction, Discord $discord) {
            LfgSlashCommandListener::act($interaction, $discord);
            LfgDeleteSlashCommandListener::act($interaction, $discord);
            LfgEditSlashCommand::act($interaction, $discord);
            VoiceChannelCreateSlashCommand::act($interaction, $discord);
            VoiceChannelDeleteSlashCommand::act($interaction, $discord);
        });

        $discord->run();
    }

    /**
     * Define the command's schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
