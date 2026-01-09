<?php
/**
 * Test Service
 * Simple endpoint to verify API Gateway and microservices are working
 */

class TestService {
    
    public function test() {
        return [
            'success' => true,
            'message' => 'API Gateway and Microservices are working!',
            'timestamp' => date('Y-m-d H:i:s'),
            'service' => 'test-microservice',
            'architecture' => 'microservices'
        ];
    }
}
?>

