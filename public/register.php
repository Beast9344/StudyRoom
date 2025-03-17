<?php
require '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = $_POST['role']; // Get the selected role

    // Validate input
    if (empty($username) || empty($email) || empty($_POST['password']) || empty($role)) {
        die("Please fill out all fields.");
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        die("Email already exists. Please use a different email.");
    }

    // Insert new user into the database
    $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("ssss", $username, $email, $password, $role);

    if ($stmt->execute()) {
        // Registration successful
        session_start();
        $_SESSION['user'] = [
            'id' => $stmt->insert_id,
            'username' => $username,
            'email' => $email,
            'role' => $role
        ];
        header("Location: index.php"); // Redirect to dashboard
        exit();
    } else {
        die("Error: " . $stmt->error);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google API Script -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body class="bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded-lg shadow-2xl w-96 transform transition-all duration-500 hover:scale-105">
        <div class="text-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Register</h1>
            <p class="text-gray-600">Create your account to get started</p>
        </div>
        <form id="signupForm" method="POST" action="" class="space-y-4">
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                <input type="text" id="username" name="username" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" id="email" name="email" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input type="password" id="password" name="password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
            </div>
            <div>
                <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                <select id="role" name="role" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                    <option value="admin">Admin</option>
                    <option value="user">User</option>
                    <option value="guest">Guest</option>
                </select>
            </div>
            <div>
                <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Register</button>
            </div>
        </form>

        <!-- Google Sign-In Button -->
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">Or Register with</p>
            <div id="g_id_onload"
                 data-client_id="849813675787-c80scu7v2gevn1fa9vvvingavj30ek9e.apps.googleusercontent.com"
                 data-context="signup"
                 data-ux_mode="popup"
                 data-callback="handleGoogleSignIn"
                 data-auto_prompt="false">
            </div>
            <div class="g_id_signin"
                 data-type="standard"
                 data-shape="rectangular"
                 data-theme="outline"
                 data-text="signup_with"
                 data-size="large"
                 data-logo_alignment="left">
            </div>
        </div>

        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">Already have an account? <a href="login.php" class="text-blue-600 hover:text-blue-500">Log in</a></p>
        </div>
    </div>

    <script>
        // Handle Google Sign-In response
        function handleGoogleSignIn(response) {
            // Decode the JWT token
            const payload = JSON.parse(atob(response.credential.split('.')[1]));

            // Extract user data
            const user = {
                name: payload.name,
                email: payload.email,
                picture: payload.picture,
                sub: payload.sub // Google's unique user ID
            };

            // Log the user data (you can send this to your backend)
            console.log("Google User Data:", user);

            // Redirect or perform further actions
            alert(`Welcome, ${user.name}!`);
            // window.location.href = "/dashboard"; // Redirect to dashboard
        }
    </script>
</body>
</html>