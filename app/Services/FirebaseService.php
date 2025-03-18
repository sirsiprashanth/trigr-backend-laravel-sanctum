<?php

namespace App\Services;

use Google\Client;
use Google\Service\Firestore;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class FirebaseService
{
    private $projectId;
    private $clientEmail;
    private $privateKey;
    private $httpClient;
    private $googleClient;
    
    public function __construct()
    {
        $credentialsPath = $this->ensureFirebaseCredentialsFile();
        $credentials = json_decode(file_get_contents($credentialsPath), true);
        
        // Validate the credentials
        if (!isset($credentials['project_id']) || !isset($credentials['client_email']) || !isset($credentials['private_key'])) {
            Log::error('Firebase credentials file is invalid or incomplete', [
                'path' => $credentialsPath,
                'has_project_id' => isset($credentials['project_id']),
                'has_client_email' => isset($credentials['client_email']),
                'has_private_key' => isset($credentials['private_key'])
            ]);
            throw new \Exception('Firebase credentials file is invalid or incomplete');
        }
        
        $this->projectId = $credentials['project_id'];
        $this->clientEmail = $credentials['client_email'];
        $this->privateKey = $credentials['private_key'];
        
        // Log some debug info about the credentials
        Log::debug('Firebase credentials loaded', [
            'project_id' => $this->projectId,
            'client_email' => $this->clientEmail,
            'private_key_length' => strlen($this->privateKey),
            'credentials_file' => $credentialsPath
        ]);
        
        // Initialize Google Client
        $this->googleClient = new Client();
        $this->googleClient->setAuthConfig($credentialsPath);
        $this->googleClient->addScope('https://www.googleapis.com/auth/datastore');
        
        // Initialize HTTP client
        $this->httpClient = new GuzzleClient([
            'base_uri' => "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/",
            'http_errors' => false
        ]);
    }
    
    /**
     * Get a Firestore document by ID
     * 
     * @param string $collection Collection name
     * @param string $documentId Document ID
     * @return array|null Document data or null if not found
     */
    public function getDocument($collection, $documentId)
    {
        try {
            $token = $this->getAccessToken();
            $response = $this->httpClient->get("{$collection}/{$documentId}", [
                'headers' => [
                    'Authorization' => "Bearer {$token}"
                ]
            ]);
            
            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody(), true);
                return $this->parseFirestoreData($data);
            }
            
            Log::error("Failed to get Firestore document: {$collection}/{$documentId}", [
                'status_code' => $response->getStatusCode(),
                'response' => json_decode($response->getBody(), true)
            ]);
            
            return null;
        } catch (\Exception $e) {
            Log::error("Error getting Firestore document: {$collection}/{$documentId}", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Update a Firestore document
     * 
     * @param string $collection Collection name
     * @param string $documentId Document ID
     * @param array $data Data to update
     * @return bool Success or failure
     */
    public function updateDocument($collection, $documentId, $data)
    {
        try {
            $token = $this->getAccessToken();
            $firestoreData = $this->prepareFirestoreData($data);
            
            $response = $this->httpClient->patch("{$collection}/{$documentId}?updateMask.fieldPaths=" . implode("&updateMask.fieldPaths=", array_keys($data)), [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'fields' => $firestoreData
                ]
            ]);
            
            if ($response->getStatusCode() == 200) {
                return true;
            }
            
            Log::error("Failed to update Firestore document: {$collection}/{$documentId}", [
                'status_code' => $response->getStatusCode(),
                'response' => json_decode($response->getBody(), true),
                'data' => $data
            ]);
            
            return false;
        } catch (\Exception $e) {
            Log::error("Error updating Firestore document: {$collection}/{$documentId}", [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            return false;
        }
    }
    
    /**
     * Query Firestore documents
     * 
     * @param string $collection Collection name
     * @param array $conditions Conditions for the query
     * @param int $limit Limit number of results
     * @param string $orderBy Field to order by
     * @param string $orderDirection Direction to order (asc or desc)
     * @return array Array of documents
     */
    public function queryDocuments($collection, $conditions = [], $limit = 10, $orderBy = null, $orderDirection = 'desc')
    {
        try {
            $token = $this->createAuthToken();
            
            $structuredQuery = [];
            
            // From collection
            $structuredQuery['from'] = [['collectionId' => $collection]];
            
            // Where conditions
            if (!empty($conditions)) {
                $structuredQuery['where'] = $this->buildWhereConditions($conditions);
            }
            
            // Order by
            if ($orderBy) {
                $structuredQuery['orderBy'] = [
                    [
                        'field' => ['fieldPath' => $orderBy],
                        'direction' => $orderDirection
                    ]
                ];
            }
            
            // Limit
            if ($limit) {
                $structuredQuery['limit'] = $limit;
            }
            
            $response = $this->httpClient->post("projects/{$this->projectId}/databases/(default):runQuery", [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'structuredQuery' => $structuredQuery
                ]
            ]);
            
            if ($response->getStatusCode() == 200) {
                $results = json_decode($response->getBody(), true);
                $documents = [];
                
                foreach ($results as $result) {
                    if (isset($result['document'])) {
                        $docData = $this->parseFirestoreData($result['document']);
                        // Get the document ID from the full path
                        $pathParts = explode('/', $result['document']['name']);
                        $docId = end($pathParts);
                        $docData['id'] = $docId;
                        $documents[] = $docData;
                    }
                }
                
                return $documents;
            }
            
            Log::error("Failed to query Firestore documents: {$collection}", [
                'status_code' => $response->getStatusCode(),
                'response' => json_decode($response->getBody(), true)
            ]);
            
            return [];
        } catch (\Exception $e) {
            Log::error("Error querying Firestore documents: {$collection}", [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Build where conditions for Firestore structured query
     * 
     * @param array $conditions Array of conditions
     * @return array Firestore structured query where clause
     */
    private function buildWhereConditions($conditions)
    {
        // For a single condition
        if (isset($conditions['field'])) {
            return [
                'fieldFilter' => [
                    'field' => ['fieldPath' => $conditions['field']],
                    'op' => $this->mapOperator($conditions['operator']),
                    'value' => $this->mapValue($conditions['value'])
                ]
            ];
        }
        
        // For multiple conditions with AND/OR
        $filters = [];
        foreach ($conditions as $condition) {
            $filters[] = [
                'fieldFilter' => [
                    'field' => ['fieldPath' => $condition['field']],
                    'op' => $this->mapOperator($condition['operator']),
                    'value' => $this->mapValue($condition['value'])
                ]
            ];
        }
        
        return [
            'compositeFilter' => [
                'op' => 'AND',
                'filters' => $filters
            ]
        ];
    }
    
    /**
     * Map PHP operators to Firestore operators
     * 
     * @param string $operator PHP operator
     * @return string Firestore operator
     */
    private function mapOperator($operator)
    {
        $map = [
            '=' => 'EQUAL',
            '!=' => 'NOT_EQUAL',
            '<' => 'LESS_THAN',
            '<=' => 'LESS_THAN_OR_EQUAL',
            '>' => 'GREATER_THAN',
            '>=' => 'GREATER_THAN_OR_EQUAL'
        ];
        
        return $map[$operator] ?? 'EQUAL';
    }
    
    /**
     * Map PHP value to Firestore value format
     * 
     * @param mixed $value PHP value
     * @return array Firestore value
     */
    private function mapValue($value)
    {
        if (is_string($value)) {
            return ['stringValue' => $value];
        } elseif (is_int($value)) {
            return ['integerValue' => $value];
        } elseif (is_float($value)) {
            return ['doubleValue' => $value];
        } elseif (is_bool($value)) {
            return ['booleanValue' => $value];
        } elseif ($value === null) {
            return ['nullValue' => null];
        } elseif (is_array($value)) {
            if (isset($value['seconds'])) {
                return ['timestampValue' => date('c', $value['seconds'])];
            }
            
            $arrayValues = [];
            foreach ($value as $item) {
                $arrayValues[] = $this->mapValue($item);
            }
            return ['arrayValue' => ['values' => $arrayValues]];
        }
        
        // Default to string
        return ['stringValue' => (string)$value];
    }
    
    /**
     * Prepare data for Firestore REST API format
     * 
     * @param array $data PHP array data
     * @return array Firestore fields data
     */
    private function prepareFirestoreData($data)
    {
        $fields = [];
        
        foreach ($data as $key => $value) {
            $fields[$key] = $this->mapValue($value);
        }
        
        return $fields;
    }
    
    /**
     * Parse Firestore data from REST API response
     * 
     * @param array $data Firestore data
     * @return array PHP array data
     */
    private function parseFirestoreData($data)
    {
        if (!isset($data['fields'])) {
            return [];
        }
        
        $result = [];
        
        foreach ($data['fields'] as $key => $value) {
            $result[$key] = $this->parseFirestoreValue($value);
        }
        
        return $result;
    }
    
    /**
     * Parse Firestore value to PHP value
     * 
     * @param array $value Firestore value
     * @return mixed PHP value
     */
    private function parseFirestoreValue($value)
    {
        if (isset($value['stringValue'])) {
            return $value['stringValue'];
        } elseif (isset($value['integerValue'])) {
            return (int) $value['integerValue'];
        } elseif (isset($value['doubleValue'])) {
            return (float) $value['doubleValue'];
        } elseif (isset($value['booleanValue'])) {
            return (bool) $value['booleanValue'];
        } elseif (isset($value['nullValue'])) {
            return null;
        } elseif (isset($value['mapValue'])) {
            return $this->parseFirestoreData($value['mapValue']);
        } elseif (isset($value['arrayValue'])) {
            $result = [];
            if (isset($value['arrayValue']['values'])) {
                foreach ($value['arrayValue']['values'] as $item) {
                    $result[] = $this->parseFirestoreValue($item);
                }
            }
            return $result;
        } elseif (isset($value['timestampValue'])) {
            return strtotime($value['timestampValue']);
        }
        
        return null;
    }
    
    /**
     * Create a JWT token for Firebase authentication
     * 
     * @return string JWT token
     */
    private function createAuthToken()
    {
        $now = time();
        
        // Log raw key stats
        Log::debug('Raw private key stats:', [
            'length' => strlen($this->privateKey),
            'has_header' => strpos($this->privateKey, '-----BEGIN PRIVATE KEY-----') !== false,
            'has_footer' => strpos($this->privateKey, '-----END PRIVATE KEY-----') !== false,
            'has_newlines' => strpos($this->privateKey, "\n") !== false,
        ]);
        
        // Create header
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
            'kid' => 'key-id' // Using default key ID for simplicity
        ];
        
        // Create payload
        $payload = [
            'iss' => $this->clientEmail,
            'sub' => $this->clientEmail,
            'aud' => 'https://firestore.googleapis.com/',
            'iat' => $now,
            'exp' => $now + 3600,
            'uid' => $this->clientEmail
        ];
        
        // Encode header and payload
        $base64UrlHeader = $this->base64UrlEncode(json_encode($header));
        $base64UrlPayload = $this->base64UrlEncode(json_encode($payload));
        
        // Create signature input
        $signatureInput = $base64UrlHeader . '.' . $base64UrlPayload;
        
        try {
            // Create signature
            openssl_sign(
                $signatureInput,
                $signature,
                $this->privateKey,
                'SHA256'
            );
            
            // Encode signature to base64url
            $base64UrlSignature = $this->base64UrlEncode($signature);
            
            // Create JWT
            return $signatureInput . '.' . $base64UrlSignature;
        } catch (\Exception $e) {
            Log::error('Error creating Firebase JWT token', [
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    /**
     * Encode data to base64url
     * 
     * @param string $data Data to encode
     * @return string Base64url encoded data
     */
    private function base64UrlEncode($data)
    {
        $base64 = base64_encode($data);
        // Convert base64 to base64url by replacing "+" with "-", "/" with "_" and removing "="
        return rtrim(strtr($base64, '+/', '-_'), '=');
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
                
                // Validate the JSON
                $credentialsArray = json_decode($jsonData, true);
                if ($credentialsArray === null) {
                    Log::error('Invalid Firebase credentials JSON provided in environment variable');
                    throw new \Exception('Invalid Firebase credentials JSON');
                }
                
                // Ensure private key is properly formatted
                if (isset($credentialsArray['private_key'])) {
                    $credentialsArray['private_key'] = $this->formatPrivateKey($credentialsArray['private_key']);
                    $jsonData = json_encode($credentialsArray, JSON_PRETTY_PRINT);
                }
            } else {
                // Create the credentials array from individual environment variables
                $privateKey = env('FIREBASE_PRIVATE_KEY', '');
                
                // Format the private key properly
                $privateKey = $this->formatPrivateKey($privateKey);
                
                $credentials = [
                    'type' => env('FIREBASE_CREDENTIALS_TYPE', 'service_account'),
                    'project_id' => env('FIREBASE_PROJECT_ID', 'trigr-community'),
                    'private_key_id' => env('FIREBASE_PRIVATE_KEY_ID', ''),
                    'private_key' => $privateKey,
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
                
                // Log the credentials for debugging
                Log::debug('Created Firebase credentials', [
                    'project_id' => $credentials['project_id'],
                    'client_email' => $credentials['client_email'],
                    'private_key_length' => strlen($credentials['private_key']),
                    'private_key_start' => substr($credentials['private_key'], 0, 50) . '...',
                    'private_key_has_header' => strpos($credentials['private_key'], '-----BEGIN PRIVATE KEY-----') === 0
                ]);
                
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
     * Format the private key to ensure it's properly structured for OpenSSL
     *
     * @param string $privateKey The private key to format
     * @return string Properly formatted private key
     */
    private function formatPrivateKey($privateKey)
    {
        // Replace string literal line breaks with actual line breaks
        $privateKey = str_replace(['\\n', '\\r'], ["\n", "\r"], $privateKey);
        
        // Strip any existing headers/footers to avoid duplication
        $keyBody = preg_replace('/-----.*?-----/', '', $privateKey);
        $keyBody = trim($keyBody);
        
        // If the key doesn't have line breaks, add them every 64 characters
        if (strpos($keyBody, "\n") === false) {
            $keyBody = wordwrap($keyBody, 64, "\n", true);
        }
        
        // Add proper header and footer
        return "-----BEGIN PRIVATE KEY-----\n" . $keyBody . "\n-----END PRIVATE KEY-----\n";
    }
    
    /**
} 