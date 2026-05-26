<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CapstoneRun extends Command
{
    /**
     * Command signature
     */
    protected $signature = '
        capstone:run
        {--port=9000 : Port number}
        {--expose : Expose to network}
    ';

    /**
     * Command description
     */
    protected $description = 'Run the Capstone Laravel server';

    /**
     * Execute the command
     */
    public function handle()
    {
        $port = $this->option('port');
        $expose = $this->option('expose');

        // Base command
        $command = "php artisan serve --port={$port}";

        // Add expose option
        if ($expose) {
            $command .= " --host=0.0.0.0";
        }

        $this->info("Starting Capstone Server...");
        $this->line($command);

        // Windows
        if (PHP_OS_FAMILY === 'Windows') {

            pclose(
                popen("start cmd /k \"$command\"", 'r')
            );

        } else {

            // Linux / Mac
            exec("$command > /dev/null 2>&1 &");
        }

        // Display URL
        if ($expose) {

            $this->info("Server exposed on port {$port}");

        } else {

            $this->info("Server running locally on:");
            $this->line("http://127.0.0.1:{$port}");
        }

        return Command::SUCCESS;
    }
}