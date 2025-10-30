<?php
/**
 * VM Controller
 */

namespace Silo\Controllers;

use Silo\Services\ApiService;

class VMController
{
    private $api;
    
    public function __construct()
    {
        $this->api = new ApiService();
    }
    
    public function list($node)
    {
        $vms = $this->api->get("/nodes/{$node}/qemu");
        require __DIR__ . '/../../public/pages/vm/list.php';
    }
    
    public function create($node)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->api->post("/nodes/{$node}/qemu", $_POST);
            header('Location: /vm/list?node=' . $node);
            exit;
        }
        
        require __DIR__ . '/../../public/pages/vm/create.php';
    }
    
    public function action($node, $vmid, $action)
    {
        $result = $this->api->post("/nodes/{$node}/qemu/{$vmid}/status/{$action}", []);
        
        return json_encode($result);
    }
}
