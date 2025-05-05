<?php
session_start();
require '../config/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if all required fields are set
    if (isset($_POST['email'], $_POST['password'], $_POST['role'])) {
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $role = trim($_POST['role']); // Get the selected role

        // Validate input
        if (empty($email) || empty($password) || empty($role)) {
            die("Please fill out all fields.");
        }

        // Fetch user from the database based on email and role
        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE email = ? AND role = ?");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("ss", $email, $role);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($id, $username, $hashed_password, $role);

        if ($stmt->fetch() && password_verify($password, $hashed_password)) {
            // Set session variables
            $_SESSION['user'] = [
                'id' => $id,
                'username' => $username,
                'email' => $email,
                'role' => $role
            ];

            // Redirect based on role
            if ($role === 'admin') {
                header("Location: admin_dashboard.php");
            } elseif ($role === 'user') {
                header("Location: index.php");
            } else {
                header("Location: guest_dashboard.php");
            }
            exit();
        } else {
            die("Invalid email, password, or role!");
        }
    } else {
        die("Please fill out all fields!");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google API Script -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    
</head>
<body class="bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center h-screen">
    
    <div class="bg-white p-8 rounded-lg shadow-2xl w-96 transform transition-all duration-500 hover:scale-105">
        <div class="text-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Login</h1>
            <p class="text-gray-600">Welcome back! Please log in to continue.</p>
        </div>

        <!-- Email/Password Login Form -->
        <form method="POST" action="" class="space-y-4">
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
                <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Log In</button>
            </div>
        </form>

        <!-- Google Sign-In Button -->
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">Or log in with</p>
            <div id="g_id_onload"
                 data-client_id="849813675787-c80scu7v2gevn1fa9vvvingavj30ek9e.apps.googleusercontent.com"
                 data-context="signin"
                 data-ux_mode="popup"
                 data-callback="handleGoogleSignIn"
                 data-auto_prompt="false">
            </div>
            <div class="g_id_signin"
                 data-type="standard"
                 data-shape="rectangular"
                 data-theme="outline"
                 data-text="signin_with"
                 data-size="large"
                 data-logo_alignment="left">
            </div>
        </div>

        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">Don't have an account? <a href="register.php" class="text-blue-600 hover:text-blue-500">Register</a></p>
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