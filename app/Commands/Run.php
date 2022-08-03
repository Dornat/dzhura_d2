<?php

namespace App\Commands;

use App\Discord\SlashCommands\LfgDeleteSlashCommandListener;
use App\Discord\SlashCommands\LfgSlashCommandListener;
use Discord\Parts\Interactions\Command\Command as DiscordCommand;
use Discord\Discord;
use Discord\Exceptions\IntentException;
use Exception;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

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
            foreach (config('slash_commands') as $opts) {
                $slashCommand = new DiscordCommand($discord, $opts);
                $discord->application->commands->save($slashCommand);
            }
        });

        $discord->listenCommand('zoo', (new LfgSlashCommandListener($discord))->listen());
        $discord->listenCommand('zoodelete', (new LfgDeleteSlashCommandListener())->listen());

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
