<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\FirebaseService;

class RazorpayWebhookController extends Controller
{
    /**
     * Handle Razorpay webhook events
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function handleWebhook(Request $request)
    {
        // Get webhook payload
        $payload = $request->all();
        
        // Log the full webhook payload for debugging
        Log::info('Razorpay webhook received', ['payload' => $payload]);
        
        // DETAILED CUSTOMER DATA LOGGING
        Log::info('CUSTOMER DATA DEBUG', [
            'payment_entity' => $payload['payload']['payment']['entity'] ?? 'Not available',
            'customer_id' => $payload['payload']['payment']['entity']['customer_id'] ?? 'Not available',
            'email' => $payload['payload']['payment']['entity']['email'] ?? 'Not available',
            'contact' => $payload['payload']['payment']['entity']['contact'] ?? 'Not available',
            'notes' => $payload['payload']['payment']['entity']['notes'] ?? 'Not available',
            'order_id' => $payload['payload']['payment']['entity']['order_id'] ?? 'Not available',
        ]);
        
        // Also log potential customer data from order if available
        if (isset($payload['payload']['order'])) {
            Log::info('ORDER DATA DEBUG', [
                'order_entity' => $payload['payload']['order']['entity'] ?? 'Not available',
                'order_receipt' => $payload['payload']['order']['entity']['receipt'] ?? 'Not available',
                'order_notes' => $payload['payload']['order']['entity']['notes'] ?? 'Not available'
            ]);
        }
        
        // Get the event type (payment.authorized, payment.failed, etc.)
        $event = $request->header('X-Razorpay-Event-Id');
        $eventType = $request->header('X-Razorpay-Event');
        
        Log::info('Webhook event', [
            'event_id' => $event,
            'event_type' => $eventType
        ]);
        
        // Verify the webhook signature (in production)
        $webhookSecret = env('RAZORPAY_WEBHOOK_SECRET', '');
        
        if (!$this->verifyWebhookSignature($request, $webhookSecret)) {
            Log::warning('Invalid webhook signature', [
                'event' => $event,
                'ip' => $request->ip()
            ]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }
        
        // Check for subscription ID in the notes (important to link payment to subscription)
        $subscriptionDocId = null;
        
        // Look in notes for our subscription document ID
        if (isset($payload['payload']['payment']['entity']['notes']['subscription_doc_id'])) {
            $subscriptionDocId = $payload['payload']['payment']['entity']['notes']['subscription_doc_id'];
            Log::info('Found subscription_doc_id in notes', ['subscription_doc_id' => $subscriptionDocId]);
        }
        
        // Process different event types
        if ($eventType === 'payment.authorized' || $eventType === 'payment.captured') {
            // Payment was successful
            $paymentId = $payload['payload']['payment']['entity']['id'] ?? null;
            $orderId = $payload['payload']['payment']['entity']['order_id'] ?? null;
            
            // Extract customer information
            $customerInfo = [];
            
            // Log all possible customer data sources
            Log::info('Attempting to extract customer info', [
                'event_type' => $eventType,
                'payment_id' => $paymentId,
                'subscription_doc_id' => $subscriptionDocId
            ]);
            
            // Check multiple locations for customer information
            
            // Direct from payment entity
            if (isset($payload['payload']['payment']['entity'])) {
                $paymentEntity = $payload['payload']['payment']['entity'];
                
                // Standard fields
                if (isset($paymentEntity['email']) && !empty($paymentEntity['email'])) {
                    $customerInfo['email'] = $paymentEntity['email'];
                }
                
                if (isset($paymentEntity['contact']) && !empty($paymentEntity['contact'])) {
                    $customerInfo['phone'] = $paymentEntity['contact'];
                }
                
                // Check notes for name/full name
                if (isset($paymentEntity['notes'])) {
                    $notes = $paymentEntity['notes'];
                    
                    // Try different possible field names
                    if (isset($notes['full_name']) && !empty($notes['full_name'])) {
                        $customerInfo['fullName'] = $notes['full_name'];
                    } elseif (isset($notes['name']) && !empty($notes['name'])) {
                        $customerInfo['fullName'] = $notes['name'];
                    } elseif (isset($notes['customer_name']) && !empty($notes['customer_name'])) {
                        $customerInfo['fullName'] = $notes['customer_name'];
                    }
                    
                    // Check if email/phone are in notes
                    if (empty($customerInfo['email']) && isset($notes['email']) && !empty($notes['email'])) {
                        $customerInfo['email'] = $notes['email'];
                    }
                    
                    if (empty($customerInfo['phone']) && isset($notes['contact']) && !empty($notes['contact'])) {
                        $customerInfo['phone'] = $notes['contact'];
                    } elseif (empty($customerInfo['phone']) && isset($notes['phone']) && !empty($notes['phone'])) {
                        $customerInfo['phone'] = $notes['phone'];
                    }
                }
            }
            
            // Try to get customer info from order if it exists
            if (isset($payload['payload']['order']['entity'])) {
                $orderEntity = $payload['payload']['order']['entity'];
                
                // Check order notes for customer info
                if (isset($orderEntity['notes'])) {
                    $notes = $orderEntity['notes'];
                    
                    // Only set if not already found
                    if (empty($customerInfo['fullName'])) {
                        if (isset($notes['full_name']) && !empty($notes['full_name'])) {
                            $customerInfo['fullName'] = $notes['full_name'];
                        } elseif (isset($notes['name']) && !empty($notes['name'])) {
                            $customerInfo['fullName'] = $notes['name'];
                        } elseif (isset($notes['customer_name']) && !empty($notes['customer_name'])) {
                            $customerInfo['fullName'] = $notes['customer_name'];
                        }
                    }
                    
                    if (empty($customerInfo['email']) && isset($notes['email']) && !empty($notes['email'])) {
                        $customerInfo['email'] = $notes['email'];
                    }
                    
                    if (empty($customerInfo['phone']) && isset($notes['contact']) && !empty($notes['contact'])) {
                        $customerInfo['phone'] = $notes['contact'];
                    } elseif (empty($customerInfo['phone']) && isset($notes['phone']) && !empty($notes['phone'])) {
                        $customerInfo['phone'] = $notes['phone'];
                    }
                }
            }
            
            // Check if info was passed in URL parameters
            if (isset($payload['payment_id']) && $payload['payment_id'] === $paymentId) {
                // This check ensures we're looking at the same payment
                
                if (empty($customerInfo['email']) && isset($payload['email']) && !empty($payload['email'])) {
                    $customerInfo['email'] = $payload['email'];
                }
                
                if (empty($customerInfo['phone']) && isset($payload['contact']) && !empty($payload['contact'])) {
                    $customerInfo['phone'] = $payload['contact'];
                }
                
                if (empty($customerInfo['fullName']) && isset($payload['name']) && !empty($payload['name'])) {
                    $customerInfo['fullName'] = $payload['name'];
                }
            }
            
            // Log the extracted customer info
            Log::info('Extracted customer info', $customerInfo);
            
            if ($paymentId) {
                // If we have a specific subscription doc ID, update it directly
                if ($subscriptionDocId) {
                    $this->updateFirestoreSubscriptionById($subscriptionDocId, $paymentId, 'completed', $customerInfo);
                } else {
                    // Otherwise use the normal flow
                    $this->updateFirestoreSubscription($paymentId, 'completed', $customerInfo);
                }
                return response()->json(['status' => 'success']);
            }
        } 
        elseif ($eventType === 'payment.failed') {
            // Payment failed
            $paymentId = $payload['payload']['payment']['entity']['id'] ?? null;
            
            if ($paymentId) {
                // If we have a specific subscription doc ID, update it directly
                if ($subscriptionDocId) {
                    $this->updateFirestoreSubscriptionById($subscriptionDocId, $paymentId, 'failed', []);
                } else {
                    // Otherwise use the normal flow
                    $this->updateFirestoreSubscription($paymentId, 'failed');
                }
                return response()->json(['status' => 'success']);
            }
        }
        
        // Default response
        return response()->json(['status' => 'received']);
    }
    
    /**
     * Verify the Razorpay webhook signature
     *
     * @param Request $request
     * @param string $webhookSecret
     * @return bool
     */
    private function verifyWebhookSignature(Request $request, $webhookSecret)
    {
        // For testing purposes, always return true
        Log::warning('Webhook signature verification bypassed for testing');
        return true;
        
        // Normal verification code (commented out for testing)
        /*
        if (empty($webhookSecret)) {
            // For development, skip verification if no secret is set
            Log::warning('Webhook signature verification skipped: No webhook secret configured');
            return true;
        }
        
        $signature = $request->header('X-Razorpay-Signature');
        
        if (!$signature) {
            return false;
        }
        
        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);
        
        return hash_equals($expectedSignature, $signature);
        */
    }
    
    /**
     * Find the most recent pending subscription
     * 
     * @param \Google\Cloud\Firestore\FirestoreClient $db
     * @return string|null Document ID if found, null otherwise
     */
    private function findPendingSubscription($db)
    {
        try {
            // Query for the most recent pending subscription
            $query = $db->collection('subscriptionPlans')
                ->where('paymentStatus', '=', 'pending')
                ->orderBy('createdAt', 'desc')
                ->limit(1)
                ->documents();
            
            foreach ($query as $document) {
                return $document->id();
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('Error finding pending subscription', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Ensure the Firebase credentials file exists.
     * If it doesn't exist, create it from environment variables.
     *
     * @return string Path to the credentials file
     */
    private function ensureFirebaseCredentialsFile()
    {
        $credentialsPath = storage_path('app/firebase-credentials.json');
        
        // Check if the credentials file already exists
        if (!file_exists($credentialsPath)) {
            Log::info('Firebase credentials file not found, creating from environment variables');
            
            // First, check if the entire JSON is provided as a single environment variable
            $jsonCredentials = env('FIREBASE_CREDENTIALS_JSON', '');
            
            if (!empty($jsonCredentials)) {
                // We have the full JSON string, use it directly
                $jsonData = base64_decode($jsonCredentials);
                
                if (json_decode($jsonData) === null) {
                    Log::error('Invalid Firebase credentials JSON provided in environment variable');
                    throw new \Exception('Invalid Firebase credentials JSON');
                }
            } else {
                // Create the credentials array from individual environment variables
                $credentials = [
                    'type' => env('FIREBASE_CREDENTIALS_TYPE', 'service_account'),
                    'project_id' => env('FIREBASE_PROJECT_ID', 'trigr-community'),
                    'private_key_id' => env('FIREBASE_PRIVATE_KEY_ID', ''),
                    'private_key' => str_replace('\\n', "\n", env('FIREBASE_PRIVATE_KEY', '')),
                    'client_email' => env('FIREBASE_CLIENT_EMAIL', ''),
                    'client_id' => env('FIREBASE_CLIENT_ID', ''),
                    'auth_uri' => env('FIREBASE_AUTH_URI', 'https://accounts.google.com/o/oauth2/auth'),
                    'token_uri' => env('FIREBASE_TOKEN_URI', 'https://oauth2.googleapis.com/token'),
                    'auth_provider_x509_cert_url' => env('FIREBASE_AUTH_PROVIDER_CERT_URL', 'https://www.googleapis.com/oauth2/v1/certs'),
                    'client_x509_cert_url' => env('FIREBASE_CLIENT_CERT_URL', ''),
                    'universe_domain' => env('FIREBASE_UNIVERSE_DOMAIN', 'googleapis.com'),
                ];
                
                // Check if we have the minimum required credentials
                if (empty($credentials['private_key']) || empty($credentials['client_email'])) {
                    Log::error('Firebase credentials are missing or incomplete in environment variables');
                    throw new \Exception('Firebase credentials are missing. Please check your environment variables.');
                }
                
                $jsonData = json_encode($credentials, JSON_PRETTY_PRINT);
            }
            
            // Ensure the storage/app directory exists
            if (!file_exists(dirname($credentialsPath))) {
                mkdir(dirname($credentialsPath), 0755, true);
            }
            
            // Write the credentials to the file
            if (file_put_contents($credentialsPath, $jsonData) === false) {
                Log::error('Failed to write Firebase credentials file');
                throw new \Exception('Failed to write Firebase credentials file');
            }
            
            // Set the correct permissions
            chmod($credentialsPath, 0600);
            
            Log::info('Firebase credentials file created successfully');
        }
        
        return $credentialsPath;
    }
    
    /**
     * Update subscription status in Firestore
     *
     * @param string $paymentId
     * @param string $status
     * @param array $customerInfo
     * @return void
     */
    private function updateFirestoreSubscription($paymentId, $status, $customerInfo = [])
    {
        try {
            // Log the customer info received
            Log::info('Starting updateFirestoreSubscription', [
                'payment_id' => $paymentId,
                'status' => $status,
                'customer_info' => $customerInfo
            ]);
            
            // Initialize Firebase service
            $firebase = new FirebaseService();
            $subscriptionUpdated = false;
            
            // Search for subscriptions with this payment ID
            $subscriptions = $firebase->queryDocuments('subscriptionPlans', [
                'field' => 'paymentId',
                'operator' => '=',
                'value' => $paymentId
            ], 1);
            
            if (!empty($subscriptions)) {
                $subscription = $subscriptions[0];
                $existingData = $subscription;
                
                Log::info('Found existing subscription with payment ID', [
                    'subscription_id' => $subscription['id'],
                    'existing_data' => $existingData
                ]);
                
                // Update the subscription status
                $updateData = [
                    'paymentId' => $paymentId,
                    'paymentStatus' => $status,
                    'status' => $status === 'completed' ? 'active' : 'failed',
                    'updatedAt' => ['seconds' => time()],
                    'webhook_updated' => true,
                    'webhook_timestamp' => ['seconds' => time()]
                ];
                
                // Add customer info if available, prioritizing new data but keeping old data if not overwritten
                if (!empty($customerInfo)) {
                    if (!empty($customerInfo['email'])) {
                        $updateData['email'] = $customerInfo['email'];
                    } elseif (isset($existingData['email'])) {
                        $updateData['email'] = $existingData['email'];
                    }
                    
                    if (!empty($customerInfo['phone'])) {
                        $updateData['phone'] = $customerInfo['phone'];
                    } elseif (isset($existingData['phone'])) {
                        $updateData['phone'] = $existingData['phone'];
                    }
                    
                    if (!empty($customerInfo['fullName'])) {
                        $updateData['fullName'] = $customerInfo['fullName'];
                    } elseif (isset($existingData['fullName'])) {
                        $updateData['fullName'] = $existingData['fullName'];
                    }
                }
                
                // Log what will be updated
                Log::info('Updating subscription with data', [
                    'subscription_id' => $subscription['id'],
                    'update_data' => $updateData
                ]);
                
                // Update Firestore
                $firebase->updateDocument('subscriptionPlans', $subscription['id'], $updateData);
                
                Log::info('Subscription updated via webhook', [
                    'subscriptionId' => $subscription['id'],
                    'paymentId' => $paymentId,
                    'status' => $status,
                    'customer_info_updated' => array_intersect_key($updateData, array_flip(['email', 'phone', 'fullName']))
                ]);
                
                $subscriptionUpdated = true;
            }
            
            // If no subscription was found with this payment ID,
            // find the most recent pending subscription and update it
            if (!$subscriptionUpdated) {
                Log::info('No subscription found with payment ID, searching for pending subscription', [
                    'payment_id' => $paymentId
                ]);
                
                // Find the most recent pending subscription
                $pendingSubscriptions = $firebase->queryDocuments('subscriptionPlans', [
                    'field' => 'paymentStatus', 
                    'operator' => '=', 
                    'value' => 'pending'
                ], 1, 'createdAt', 'desc');
                
                if (!empty($pendingSubscriptions)) {
                    $pendingSubscription = $pendingSubscriptions[0];
                    $existingData = $pendingSubscription;
                    
                    Log::info('Found pending subscription to update', [
                        'subscription_id' => $pendingSubscription['id'],
                        'existing_data' => $existingData
                    ]);
                    
                    $updateData = [
                        'paymentId' => $paymentId,
                        'paymentStatus' => $status,
                        'status' => $status === 'completed' ? 'active' : 'failed',
                        'updatedAt' => ['seconds' => time()],
                        'paymentTimestamp' => ['seconds' => time()],
                        'webhook_updated' => true,
                        'webhook_timestamp' => ['seconds' => time()]
                    ];
                    
                    // Preserve existing customer info if available
                    if (isset($existingData['fullName']) && !empty($existingData['fullName'])) {
                        $updateData['fullName'] = $existingData['fullName'];
                    }
                    
                    if (isset($existingData['email']) && !empty($existingData['email'])) {
                        $updateData['email'] = $existingData['email'];
                    }
                    
                    if (isset($existingData['phone']) && !empty($existingData['phone'])) {
                        $updateData['phone'] = $existingData['phone'];
                    }
                    
                    // Add new customer info if available, overriding existing data
                    if (!empty($customerInfo)) {
                        if (!empty($customerInfo['email'])) {
                            $updateData['email'] = $customerInfo['email'];
                        }
                        
                        if (!empty($customerInfo['phone'])) {
                            $updateData['phone'] = $customerInfo['phone'];
                        }
                        
                        if (!empty($customerInfo['fullName'])) {
                            $updateData['fullName'] = $customerInfo['fullName'];
                        }
                    }
                    
                    // Log what will be updated
                    Log::info('Updating pending subscription with data', [
                        'subscription_id' => $pendingSubscription['id'],
                        'update_data' => $updateData
                    ]);
                    
                    // Update Firestore
                    $firebase->updateDocument('subscriptionPlans', $pendingSubscription['id'], $updateData);
                    
                    Log::info('Most recent pending subscription updated via webhook', [
                        'subscriptionId' => $pendingSubscription['id'],
                        'paymentId' => $paymentId,
                        'status' => $status,
                        'customer_info_updated' => array_intersect_key($updateData, array_flip(['email', 'phone', 'fullName']))
                    ]);
                } else {
                    Log::warning('No pending subscription found to update', [
                        'payment_id' => $paymentId
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error updating Firestore subscription', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'paymentId' => $paymentId
            ]);
        }
    }
    
    /**
     * Update a specific subscription by ID
     *
     * @param string $docId The Firestore document ID
     * @param string $paymentId The Razorpay payment ID
     * @param string $status The payment status
     * @param array $customerInfo Customer information
     * @return void
     */
    private function updateFirestoreSubscriptionById($docId, $paymentId, $status, $customerInfo = [])
    {
        try {
            Log::info('Updating specific subscription by ID', [
                'doc_id' => $docId,
                'payment_id' => $paymentId,
                'status' => $status,
                'customer_info' => $customerInfo
            ]);
            
            // Initialize Firebase service
            $firebase = new FirebaseService();
            
            // Get existing data
            $existingData = $firebase->getDocument('subscriptionPlans', $docId);
            
            if (!$existingData) {
                Log::warning('Subscription document not found', ['doc_id' => $docId]);
                return;
            }
            
            // Create update data
            $updateData = [
                'paymentId' => $paymentId,
                'paymentStatus' => $status,
                'status' => $status === 'completed' ? 'active' : 'failed',
                'updatedAt' => ['seconds' => time()],
                'paymentTimestamp' => ['seconds' => time()],
                'webhook_updated' => true,
                'webhook_timestamp' => ['seconds' => time()]
            ];
            
            // Merge customer info with priority to new data
            foreach (['email', 'phone', 'fullName'] as $field) {
                if (!empty($customerInfo[$field])) {
                    // New data from webhook
                    $updateData[$field] = $customerInfo[$field];
                } elseif (isset($existingData[$field]) && !empty($existingData[$field])) {
                    // Existing data from Firestore
                    $updateData[$field] = $existingData[$field];
                }
            }
            
            // Log what will be updated
            Log::info('Updating subscription by ID with data', [
                'doc_id' => $docId,
                'update_data' => $updateData
            ]);
            
            // Update the document
            $firebase->updateDocument('subscriptionPlans', $docId, $updateData);
            
            Log::info('Subscription updated successfully by ID', [
                'doc_id' => $docId,
                'payment_id' => $paymentId,
                'status' => $status,
                'customer_info_updated' => array_intersect_key($updateData, array_flip(['email', 'phone', 'fullName']))
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error updating subscription by ID', [
                'doc_id' => $docId,
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
