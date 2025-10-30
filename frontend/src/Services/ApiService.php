<?php
/**
 * API Service - Communicates with Flask backend
 */

namespace Silo\Services;

class ApiService
{
    private $baseUrl;
    private $timeout;
    
    public function __construct()
    {
        $config = require __DIR__ . '/../Config/config.php';
        $api = $config['api'];
        
        $this->baseUrl = "http://{$api['host']}:{$api['port']}{$api['prefix']}";
        $this->timeout = $api['timeout'];
    }
    
    /**
     * Make API request
     */
    public function request($method, $endpoint, $data = null)
    {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        
        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen(json_encode($data))
            ]);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        if ($response === false) {
            return ['success' => false, 'error' => 'API request failed'];
        }
        
        $result = json_decode($response, true);
        
        if ($httpCode >= 400) {
            return ['success' => false, 'error' => $result['error'] ?? 'Unknown error'];
        }
        
        return $result;
    }
    
    public function get($endpoint)
    {
        return $this->request('GET', $endpoint);
    }
    
    public function post($endpoint, $data)
    {
        return $this->request('POST', $endpoint, $data);
    }
    
    public function put($endpoint, $data)
    {
        return $this->request('PUT', $endpoint, $data);
    }
    
    public function delete($endpoint)
    {
        return $this->request('DELETE', $endpoint);
    }
}
