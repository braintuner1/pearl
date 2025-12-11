<?php
// momocollection.php
require_once 'config.php';

class MTNCollection {
    private $apiUserId;
    private $apiKey;
    private $subscriptionKey;
    private $environment;
    
    public function __construct($subscriptionKey) {
        $this->subscriptionKey = $subscriptionKey;
        $this->environment = API_ENVIRONMENT;
        $this->apiUserId = COLLECTION_API_USER_ID;
        $this->apiKey = COLLECTION_API_KEY;
    }
    
    public function requestToPay($amount, $phone, $externalId, $payerMessage = '', $payeeNote = '') {
        // Remove leading 0 if present and add country code
        $formattedPhone = $this->formatPhoneNumber($phone);
        
        // Generate reference ID
        $referenceId = uniqid('PEF_');
        
        $data = [
            'amount' => (string)$amount,
            'currency' => CURRENCY,
            'externalId' => $externalId,
            'payer' => [
                'partyIdType' => 'MSISDN',
                'partyId' => $formattedPhone
            ],
            'payerMessage' => $payerMessage ?: 'Donation to Pearl Edu Fund',
            'payeeNote' => $payeeNote ?: 'Thank you for your donation'
        ];
        
        $url = API_BASE_URL . '/collection/v1_0/requesttopay';
        
        $headers = [
            'X-Reference-Id: ' . $referenceId,
            'X-Target-Environment: ' . $this->environment,
            'Ocp-Apim-Subscription-Key: ' . $this->subscriptionKey,
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->getToken()
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 202) {
            return [
                'success' => true,
                'reference_id' => $referenceId,
                'transaction_id' => $externalId,
                'message' => 'Payment request sent to customer'
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Failed to initiate payment: HTTP ' . $httpCode,
                'response' => $response
            ];
        }
    }
    
    public function getPaymentStatus($referenceId) {
        $url = API_BASE_URL . '/collection/v1_0/requesttopay/' . $referenceId;
        
        $headers = [
            'X-Target-Environment: ' . $this->environment,
            'Ocp-Apim-Subscription-Key: $this->subscriptionKey,
            'Authorization: Bearer ' . $this->getToken()
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            return json_decode($response, true);
        }
        
        return null;
    }
    
    private function getToken() {
        // Check if token exists and is valid
        if (isset($_SESSION['momo_token']) && isset($_SESSION['momo_token_expiry']) && 
            time() < $_SESSION['momo_token_expiry']) {
            return $_SESSION['momo_token'];
        }
        
        // Generate new token
        $url = API_BASE_URL . '/collection/token/';
        
        $headers = [
            'Authorization: Basic ' . base64_encode($this->apiUserId . ':' . $this->apiKey),
            'Ocp-Apim-Subscription-Key: ' . $this->subscriptionKey
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            if (isset($data['access_token'])) {
                $_SESSION['momo_token'] = $data['access_token'];
                $_SESSION['momo_token_expiry'] = time() + 3500; // Token expires in ~1 hour
                return $data['access_token'];
            }
        }
        
        throw new Exception('Failed to get access token');
    }
    
    private function formatPhoneNumber($phone) {
        // Remove any non-digit characters
        $phone = preg_replace('/\D/', '', $phone);
        
        // If starts with 0, replace with 256
        if (substr($phone, 0, 1) === '0') {
            $phone = '256' . substr($phone, 1);
        }
        
        // If starts with +256, remove the +
        if (substr($phone, 0, 4) === '+256') {
            $phone = substr($phone, 1);
        }
        
        return $phone;
    }
}
?>