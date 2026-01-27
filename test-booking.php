<?php
header('Content-Type: application/json');

// Simple test
echo json_encode([
    'success' => true,
    'message' => 'PHP is working!',
    'method' => $_SERVER['REQUEST_METHOD']
]);
?>
