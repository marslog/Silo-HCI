<?php
/**
 * Dashboard Controller
 */

namespace Silo\Controllers;

use Silo\Services\ApiService;

class DashboardController
{
    private $api;
    
    public function __construct()
    {
        $this->api = new ApiService();
    }
    
    public function index()
    {
        $summary = $this->api->get('/monitoring/summary');
        $nodes = $this->api->get('/nodes');
        
        require __DIR__ . '/../../public/pages/dashboard.php';
    }
    
    public function getSummary()
    {
        return $this->api->get('/monitoring/summary');
    }
}
