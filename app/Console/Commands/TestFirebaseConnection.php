<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Services\FirebaseService;

class TestFirebaseConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:firebase';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the Firebase connection';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Firebase connection...');
        
        try {
            // Create FirebaseService instance
            $firebase = new FirebaseService();
            
            // Check if credentials file exists
            $credentialsPath = storage_path('app/firebase-credentials.json');
            $this->info("Credentials file: {$credentialsPath}");
            
            if (file_exists($credentialsPath)) {
                $this->info('✅ Credentials file exists');
                $credentials = json_decode(file_get_contents($credentialsPath), true);
                
                $this->info('Project ID: ' . $credentials['project_id']);
                $this->info('Client Email: ' . $credentials['client_email']);
            } else {
                $this->error('❌ Credentials file does not exist');
                return;
            }
            
            // Query subscriptions collection
            $this->info('Querying subscriptionPlans collection...');
            
            $subscriptions = $firebase->queryDocuments('subscriptionPlans', [], 1);
            
            if (empty($subscriptions)) {
                $this->info('No subscriptions found, but connection successful');
            } else {
                $this->info('✅ Successfully retrieved ' . count($subscriptions) . ' subscription(s)');
                
                foreach ($subscriptions as $subscription) {
                    $this->info('Document ID: ' . $subscription['id']);
                    $this->info('Document data: ' . json_encode(array_slice($subscription, 0, 5), JSON_PRETTY_PRINT));
                }
            }
            
            $this->info('Firebase connection test completed successfully!');
            
        } catch (\Exception $e) {
            $this->error('Error connecting to Firebase: ' . $e->getMessage());
            $this->line($e->getTraceAsString());
        }
    }
} 