<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestMail extends Command
{
    protected $signature = 'mail:test {email?}';
    protected $description = 'Send a test email';

    public function handle()
    {
        $email = $this->argument('email') ?? 'test@example.com';
        
        $this->info("Sending test email to: " . $email);
        
        try {
            Mail::raw('Test email from Laravel Command', function($message) use ($email) {
                $message->to($email)
                       ->subject('Test Email from Artisan Command');
            });
            
            $this->info('Email sent successfully!');
            $this->info('Mail configuration:');
            $this->table(
                ['Setting', 'Value'],
                [
                    ['Driver', config('mail.default')],
                    ['Host', config('mail.mailers.smtp.host')],
                    ['Port', config('mail.mailers.smtp.port')],
                    ['Username', config('mail.mailers.smtp.username')],
                    ['From Address', config('mail.from.address')],
                ]
            );
        } catch (\Exception $e) {
            $this->error('Failed to send email: ' . $e->getMessage());
        }
    }
} 