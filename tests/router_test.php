<?php
require 'C:/xampp/htdocs/AntiScam/app/Support/Autoloader.php';
App\Support\Autoloader::register();

$r = new App\Support\Router();
$r->get('/', function(){ echo 'MATCHED /'; });
$r->get('/analyze', function(){ echo 'MATCHED /analyze'; });
$r->setNotFound(function(){ echo 'NOT FOUND'; });

// Simulate subfolder request
$_SERVER['SCRIPT_NAME'] = '/AntiScam/public/index.php';
echo "Test 1 (subfolder root): ";
$r->dispatch('GET', '/AntiScam/public/');
echo "\n";

echo "Test 2 (subfolder /analyze): ";
$r->dispatch('GET', '/AntiScam/public/analyze');
echo "\n";

// Simulate vhost root
$_SERVER['SCRIPT_NAME'] = '/index.php';
echo "Test 3 (vhost /): ";
$r->dispatch('GET', '/');
echo "\n";

echo "Test 4 (vhost /analyze): ";
$r->dispatch('GET', '/analyze');
echo "\n";

echo "DONE\n";
