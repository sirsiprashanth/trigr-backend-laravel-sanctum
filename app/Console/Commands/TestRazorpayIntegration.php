<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Http\Controllers\RazorpayWebhookController;
use App\Services\FirebaseService;

class TestRazorpayIntegration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:razorpay {action=all : Action to test (webhook, credentials, all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the Razorpay integration with Firebase';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        
        $this->info('Starting Razorpay integration tests...');
        
        // Test Firebase Credentials
        if ($action === 'credentials' || $action === 'all') {
            $this->testFirebaseCredentials();
        }
        
        // Test Webhook
        if ($action === 'webhook' || $action === 'all') {
            $this->testWebhook();
        }
        
        $this->info('All tests completed!');
    }
    
    /**
     * Test Firebase credentials and Firestore connection
     */
    private function testFirebaseCredentials()
    {
        $this->info('Testing Firebase credentials...');
        
        try {
            // Create Firebase service instance
            $firebase = new FirebaseService();
            
            // Check if credentials file exists
            $credentialsPath = storage_path('app/firebase-credentials.json');
            $this->info("Firebase credentials file: {$credentialsPath}");
            
            if (file_exists($credentialsPath)) {
                $this->info('✅ Credentials file exists');
                
                // Check if file is readable
                if (is_readable($credentialsPath)) {
                    $this->info('✅ Credentials file is readable');
                    
                    // Check if the file content is valid JSON
                    $contents = file_get_contents($credentialsPath);
                    $json = json_decode($contents);
                    
                    if ($json !== null) {
                        $this->info('✅ Credentials file contains valid JSON');
                        
                        // Check required fields
                        $requiredFields = ['type', 'project_id', 'private_key', 'client_email'];
                        $missingFields = [];
                        
                        foreach ($requiredFields as $field) {
                            if (!isset($json->$field) || empty($json->$field)) {
                                $missingFields[] = $field;
                            }
                        }
                        
                        if (count($missingFields) === 0) {
                            $this->info('✅ Credentials file contains all required fields');
                        } else {
                            $this->error('❌ Credentials file is missing required fields: ' . implode(', ', $missingFields));
                        }
                    } else {
                        $this->error('❌ Credentials file does not contain valid JSON');
                    }
                } else {
                    $this->error('❌ Credentials file is not readable');
                }
            } else {
                $this->error('❌ Credentials file does not exist');
            }
            
            // Test connection to Firestore
            $this->info('Attempting to connect to Firestore...');
            
            // Test query
            $documents = $firebase->queryDocuments('subscriptionPlans', [], 1);
            
            if (empty($documents)) {
                $this->info('❕ No documents found in subscriptionPlans collection, but connection was successful');
            } else {
                $this->info('✅ Successfully fetched a document from subscriptionPlans collection');
                $this->info("Document ID: {$documents[0]['id']}");
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Error testing Firebase credentials: ' . $e->getMessage());
            $this->line($e->getTraceAsString());
        }
    }
    
    /**
     * Test the webhook by sending a simulated webhook request
     */
    private function testWebhook()
    {
        $this->info('Testing Razorpay webhook...');
        
        try {
            // Create a test subscription in Firestore
            $subscriptionId = $this->createTestSubscription();
            
            if (!$subscriptionId) {
                $this->error('❌ Failed to create test subscription');
                return;
            }
            
            $this->info("Created test subscription with ID: {$subscriptionId}");
            
            // Create a simulated webhook payload
            $testPaymentId = 'test_payment_' . time();
            $payload = [
                'payload' => [
                    'payment' => [
                        'entity' => [
                            'id' => $testPaymentId,
                            'notes' => [
                                'subscription_doc_id' => $subscriptionId
                            ],
                            'email' => 'test@example.com',
                            'contact' => '1234567890'
                        ]
                    ]
                ]
            ];
            
            $this->info('Simulating webhook request...');
            
            // Create a mock request
            $request = Request::create(
                '/api/webhooks/razorpay',
                'POST',
                $payload
            );
            
            // Add Razorpay headers
            $request->headers->set('X-Razorpay-Event-Id', 'test_event_' . time());
            $request->headers->set('X-Razorpay-Event', 'payment.captured');
            
            // Create webhook controller
            $controller = new RazorpayWebhookController();
            
            // Process the webhook
            $response = $controller->handleWebhook($request);
            
            // Check response
            if ($response->getStatusCode() === 200) {
                $this->info('✅ Webhook controller returned 200 OK response');
                
                // Check if subscription was updated in Firestore
                $this->info('Checking if subscription was updated...');
                $updated = $this->checkSubscriptionUpdated($subscriptionId);
                
                if ($updated) {
                    $this->info('✅ Subscription was successfully updated by webhook');
                } else {
                    $this->error('❌ Subscription was not updated by webhook');
                }
            } else {
                $this->error('❌ Webhook controller returned non-200 response: ' . $response->getStatusCode());
                $this->line($response->getContent());
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Error testing webhook: ' . $e->getMessage());
            $this->line($e->getTraceAsString());
        }
    }
    
    /**
     * Create a test subscription for webhook testing
     * 
     * @return string|null Subscription ID if created, null otherwise
     */
    private function createTestSubscription()
    {
        try {
            // Initialize Firebase service
            $firebase = new FirebaseService();
            
            // Generate a subscription ID
            $subscriptionId = 'TEST' . rand(100000, 999999);
            
            // Calculate start and end dates
            $startDate = new \DateTime();
            $endDate = (new \DateTime())->modify('+1 month');
            
            // Create subscription data
            $subscriptionData = [
                'subscriptionId' => $subscriptionId,
                'planType' => 'trigr_plus',
                'amount' => 649,
                'startDate' => ['seconds' => $startDate->getTimestamp()],
                'endDate' => ['seconds' => $endDate->getTimestamp()],
                'createdAt' => ['seconds' => time()],
                'status' => 'pending',
                'paymentStatus' => 'pending',
                'fullName' => 'Test User',
                'email' => 'test@example.com',
                'phone' => '1234567890',
                'test_subscription' => true
            ];
            
            // Create document in Firestore
            $docId = $this->createDocument($firebase, 'subscriptionPlans', $subscriptionData);
            
            return $docId;
            
        } catch (\Exception $e) {
            $this->error('Error creating test subscription: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create a document in Firestore
     * 
     * @param FirebaseService $firebase Firebase service
     * @param string $collection Collection name
     * @param array $data Document data
     * @return string|null Document ID if created, null otherwise
     */
    private function createDocument($firebase, $collection, $data)
    {
        try {
            // Get mock document ID for testing
            $docId = 'test_' . uniqid();
            $this->info("Creating test document with ID: {$docId}");
            
            // Create document by updating it with all the data
            $result = $firebase->updateDocument($collection, $docId, $data);
            
            if ($result) {
                $this->info("Document created successfully");
                return $docId;
            }
            
            $this->error("Failed to create document");
            return null;
        } catch (\Exception $e) {
            $this->error('Error creating Firestore document: ' . $e->getMessage());
            $this->line($e->getTraceAsString());
            return null;
        }
    }
    
    /**
     * Check if a subscription was updated by the webhook
     * 
     * @param string $subscriptionId
     * @return bool
     */
    private function checkSubscriptionUpdated($subscriptionId)
    {
        try {
            // Initialize Firebase service
            $firebase = new FirebaseService();
            
            // Get the subscription
            $data = $firebase->getDocument('subscriptionPlans', $subscriptionId);
            
            if (!$data) {
                $this->error('Test subscription not found');
                return false;
            }
            
            // Check if updated by webhook
            if (
                isset($data['webhook_updated']) && 
                $data['webhook_updated'] === true &&
                isset($data['status']) && 
                $data['status'] === 'active' &&
                isset($data['paymentStatus']) && 
                $data['paymentStatus'] === 'completed'
            ) {
                return true;
            }
            
            // Log what we found
            $this->line('Current subscription data:');
            $this->line('Status: ' . ($data['status'] ?? 'N/A'));
            $this->line('Payment Status: ' . ($data['paymentStatus'] ?? 'N/A'));
            $this->line('Webhook Updated: ' . (isset($data['webhook_updated']) ? ($data['webhook_updated'] ? 'Yes' : 'No') : 'N/A'));
            
            return false;
            
        } catch (\Exception $e) {
            $this->error('Error checking subscription update: ' . $e->getMessage());
            return false;
        }
    }
} 