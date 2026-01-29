<?php
// Firebase Cloud Messaging Helper
// Sends notifications via Google Firebase

class FirebaseNotification {
    private $projectId;
    private $serviceAccountPath;
    private $accessToken;
    
    public function __construct() {
        $this->serviceAccountPath = __DIR__ . '/firebase-config.json';
        $this->projectId = 'oikos-orchard-and-farm';
    }
    
    /**
     * Get access token from Firebase service account
     */
    private function getAccessToken() {
        if ($this->accessToken && time() < $this->accessTokenExpiry) {
            return $this->accessToken;
        }
        
        $serviceAccount = json_decode(file_get_contents($this->serviceAccountPath), true);
        
        $now = time();
        $header = base64_encode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT'
        ]));
        
        $payload = base64_encode(json_encode([
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => $serviceAccount['token_uri'],
            'exp' => $now + 3600,
            'iat' => $now
        ]));
        
        $signature = '';
        $privateKey = $serviceAccount['private_key'];
        openssl_sign("$header.$payload", $signature, $privateKey, 'sha256');
        $signature = base64_encode($signature);
        
        $jwt = "$header.$payload.$signature";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $serviceAccount['token_uri']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $tokenData = json_decode($response, true);
        $this->accessToken = $tokenData['access_token'];
        $this->accessTokenExpiry = time() + ($tokenData['expires_in'] - 300);
        
        return $this->accessToken;
    }
    
    /**
     * Send notification via FCM
     * @param string $deviceToken - Device token from mobile app
     * @param string $title - Notification title
     * @param string $body - Notification body/message
     * @param array $data - Additional data (optional)
     */
    public function sendNotification($deviceToken, $title, $body, $data = []) {
        try {
            $accessToken = $this->getAccessToken();
            
            $message = [
                'message' => [
                    'token' => $deviceToken,
                    'notification' => [
                        'title' => $title,
                        'body' => $body
                    ],
                    'data' => $data
                ]
            ];
            
            $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                return [
                    'success' => true,
                    'message' => 'Notification sent successfully'
                ];
            } else {
                error_log("FCM Error: " . $response);
                return [
                    'success' => false,
                    'message' => 'Failed to send notification'
                ];
            }
        } catch (Exception $e) {
            error_log("FCM Exception: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Send topic notification (to multiple devices subscribed to a topic)
     * @param string $topic - Topic name
     * @param string $title - Notification title
     * @param string $body - Notification body
     * @param array $data - Additional data
     */
    public function sendTopicNotification($topic, $title, $body, $data = []) {
        try {
            $accessToken = $this->getAccessToken();
            
            $message = [
                'message' => [
                    'topic' => $topic,
                    'notification' => [
                        'title' => $title,
                        'body' => $body
                    ],
                    'data' => $data
                ]
            ];
            
            $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                return [
                    'success' => true,
                    'message' => 'Topic notification sent successfully'
                ];
            } else {
                error_log("FCM Topic Error: " . $response);
                return [
                    'success' => false,
                    'message' => 'Failed to send topic notification'
                ];
            }
        } catch (Exception $e) {
            error_log("FCM Topic Exception: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage()
            ];
        }
    }
}

?>
