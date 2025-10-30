<?php
/**
 * Node Controller
 */

namespace Silo\Controllers;

use Silo\Services\ApiService;

class NodeController
{
    private $api;
    
    public function __construct()
    {
        $this->api = new ApiService();
    }
    
    public function list()
    {
        $nodes = $this->api->get('/nodes');
        require __DIR__ . '/../../public/pages/nodes/list.php';
    }
    
    public function detail($node)
    {
        $nodeInfo = $this->api->get("/nodes/{$node}");
        $vms = $this->api->get("/nodes/{$node}/vms");
        $containers = $this->api->get("/nodes/{$node}/containers");
        
        require __DIR__ . '/../../public/pages/nodes/detail.php';
    }
}
