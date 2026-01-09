<?php
/**
 * Authentication Service
 * Handles user authentication and login
 */

class AuthService {
    
    public function login($data) {
        // Validate input
        if (!isset($data['email']) || !isset($data['password'])) {
            return [
                'success' => false,
                'message' => 'Email and password are required'
            ];
        }

        $email = trim($data['email']);
        $password = $data['password'];

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Invalid email format'
            ];
        }

        // Connect to database
        try {
            $conn = db();

            // Prepare statement to get user
            $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
            
            if (!$stmt) {
                return [
                    'success' => false,
                    'message' => 'Database query preparation failed'
                ];
            }

            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                $stmt->close();
                $conn->close();
                return [
                    'success' => false,
                    'message' => 'Invalid email or password'
                ];
            }

            $user = $result->fetch_assoc();

            // Verify password
            if (!password_verify($password, $user['password'])) {
                $stmt->close();
                $conn->close();
                return [
                    'success' => false,
                    'message' => 'Invalid email or password'
                ];
            }

            // Generate a simple token (in production, use JWT)
            $token = bin2hex(random_bytes(32));

            $stmt->close();
            $conn->close();

            // Return success response
            return [
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'id' => (int)$user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ],
                'token' => $token
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'An error occurred during login',
                'error' => $e->getMessage()
            ];
        }
    }
}
?>

