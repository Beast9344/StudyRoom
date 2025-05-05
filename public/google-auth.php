<?php
require '../config/config.php';

if (isset($_POST['credential'])) {
    $id_token = $_POST['credential'];
    
    // Verify the Google ID token
    $client = new Google_Client(['client_id' => 'YOUR_CLIENT_ID.apps.googleusercontent.com']);
    $payload = $client->verifyIdToken($id_token);
    
    if ($payload) {
        $google_id = $payload['sub'];
        $email = $payload['email'];
        $username = $payload['name'];
        
        // Check if user exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            // Register new user
            $insert_stmt = $conn->prepare("INSERT INTO users (username, email, google_id) VALUES (?, ?, ?)");
            $insert_stmt->bind_param("sss", $username, $email, $google_id);
            $insert_stmt->execute();
        }
        
        // Log the user in
        $_SESSION['user'] = [
            'email' => $email,
            'username' => $username
        ];
        header("Location: index.php");
        exit();
    } else {
        die("Invalid ID token");
    }
}
?>