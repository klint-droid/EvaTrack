<?php

namespace App\Console\Commands;

use App\Services\TextBeeService;
use Illuminate\Console\Command;

class TestTextBee extends Command
{
    protected $signature = 'textbee:test
                            {phone : Recipient phone number in E.164 format e.g. +639171234567}
                            {--message= : Custom message (defaults to a test string)}';

    protected $description = 'Send a test SMS via TextBee to verify the integration';

    public function handle(TextBeeService $sms): int
    {
        $phone   = $this->argument('phone');
        $message = $this->option('message') ?? 'Test SMS from TextBee integration ✅';

        $this->info("Sending SMS to {$phone}...");

        $result = $sms->send($phone, $message);

        if ($result['success']) {
            $this->info('SMS sent successfully!');
            $this->line('   Message ID : ' . ($result['message_id'] ?? 'n/a'));
        } else {
            $this->error('SMS failed: ' . $result['error']);
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}