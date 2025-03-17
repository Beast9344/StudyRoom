<?php
require '../config/config.php';
require '../utils/auth.php';

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Fetch user data from the database
$user_id = $_SESSION['user'];
$sql = "SELECT id, username, email, role, profile_picture FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($id, $username, $email, $role, $profile_picture);
$stmt->fetch();
$stmt->close();

// Fetch tasks
$tasks = [];
$sql = "SELECT id, title, description FROM tasks WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $tasks[] = $row;
    }
}
$stmt->close();

// Fetch study rooms
$rooms = [];
$sql = "SELECT id, name, participants FROM rooms";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $rooms[] = $row;
    }
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-900 text-white">
    <!-- Main Container -->
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-800 p-4">
            <h1 class="text-xl font-bold mb-6">Study Room</h1>
            <ul class="space-y-2">
                <!-- Create Room -->
                <li>
                    <a href="#" class="flex items-center p-2 bg-blue-600 rounded hover:bg-blue-700 transition">
                        <i class="fas fa-plus mr-2"></i>
                        <span class="button-text">Create Room</span>
                    </a>
                </li>
                <!-- Task -->
                <li>
                    <a href="#" class="flex items-center p-2 bg-blue-600 rounded hover:bg-blue-700 transition">
                        <i class="fas fa-tasks mr-2"></i>
                        <span class="button-text">Task</span>
                    </a>
                </li>
                <!-- Chat -->
                <li>
                    <a href="#" class="flex items-center p-2 bg-blue-600 rounded hover:bg-blue-700 transition">
                        <i class="fas fa-comments mr-2"></i>
                        <span class="button-text">Chat</span>
                    </a>
                </li>
                <!-- Friends -->
                <li>
                    <a href="#" class="flex items-center p-2 bg-blue-600 rounded hover:bg-blue-700 transition">
                        <i class="fas fa-users mr-2"></i>
                        <span class="button-text">Friends</span>
                    </a>
                </li>
                <!-- Live Meet -->
                <li>
                    <a href="#" class="flex items-center p-2 bg-blue-600 rounded hover:bg-blue-700 transition">
                        <i class="fas fa-video mr-2"></i>
                        <span class="button-text">Live Meet</span>
                    </a>
                </li>
                <!-- Teachers Zone -->
                <li>
                    <a href="#" class="flex items-center p-2 bg-blue-600 rounded hover:bg-blue-700 transition">
                        <i class="fas fa-chalkboard-teacher mr-2"></i>
                        <span class="button-text">Teachers Zone</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-8">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-4xl font-bold">KNOWLEDGE IS POWER</h1>
                
                <!-- Profile & Notification Icons -->
                <div class="flex gap-4">
                    <button class="p-2 hover:bg-gray-800 rounded-full">
                        🔔
                    </button>
                    <div class="relative">
                        <button id="profileBtn" class="p-2 hover:bg-gray-800 rounded-full">
                            👤
                        </button>
                        
                        <!-- Profile Dropdown -->
                        <div id="profileDropdown" class="hidden absolute right-0 mt-2 w-48 bg-gray-800 rounded-lg shadow-xl">
                            <div class="p-4 space-y-4">
                                <button class="block w-full text-left hover:bg-gray-700 p-2 rounded">
                                    Profile
                                </button>
                                <button class="block w-full text-left hover:bg-gray-700 p-2 rounded">
                                    Change Password
                                </button>
                                <button class="block w-full text-left hover:bg-gray-700 p-2 rounded">
                                    Notification Settings
                                </button>
                                <button class="block w-full text-left hover:bg-gray-700 p-2 rounded">
                                    Delete Account
                                </button>
                                <button class="block w-full text-left text-red-500 hover:bg-gray-700 p-2 rounded">
                                    Logout
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Create Room Section -->
            <div class="mb-12">
                <h2 class="text-2xl mb-4">Create Your Room</h2>
                <div class="flex gap-4 mb-8">
                    <button class="bg-blue-600 px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                        Create
                    </button>
                    <button class="bg-gray-800 px-6 py-3 rounded-lg hover:bg-gray-700 transition">
                        Join Feature Room
                    </button>
                </div>

                <!-- Rooms List -->
                <div class="space-y-6">
                    <?php foreach ($rooms as $room): ?>
                        <div class="bg-gray-800 p-6 rounded-lg hover:bg-gray-750 transition">
                            <h3 class="text-xl font-bold"><?= htmlspecialchars($room['name']); ?></h3>
                            <p class="text-gray-400"><?= htmlspecialchars($room['participants']); ?> Participants</p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>


            <!-- Tasks Section -->
            <div class="mb-12">
                <h2 class="text-2xl mb-4">Your Tasks</h2>
                <div class="space-y-6">
                    <?php foreach ($tasks as $task): ?>
                        <div class="bg-gray-800 p-6 rounded-lg hover:bg-gray-750 transition">
                            <h3 class="text-xl font-bold"><?= htmlspecialchars($task['title']); ?></h3>
                            <p class="text-gray-400"><?= htmlspecialchars($task['description']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
        </div>
        </div>

<!-- Profile Page (Hidden by default) -->
<div id="profilePage" class="hidden w-96 p-8 border-l border-gray-700">
    <!-- Back Button -->
    <button onclick="hideProfilePage()" class="mb-4 p-2 bg-gray-700 rounded hover:bg-gray-600">
        ← Back
    </button>

    <div class="mb-8">
        <div class="relative w-32 h-32 mb-4">
            <img src="<?= htmlspecialchars($profile_picture); ?>" alt="Profile" 
                 class="w-full h-full rounded-full object-cover">
            <button onclick="openUploadModal()"
                    class="absolute bottom-0 right-0 bg-blue-600 p-2 rounded-full hover:bg-blue-700">
                ✏️
            </button>
        </div>
        
        <h2 class="text-2xl font-bold mb-4">Your Profile</h2>
        <div class="space-y-4">
            <div>
                <h3 class="font-bold">Username:</h3>
                <p><?= htmlspecialchars($username); ?></p>
            </div>
            <div>
                <h3 class="font-bold">Email:</h3>
                <p><?= htmlspecialchars($email); ?></p>
            </div>
        </div>
    </div>

    <!-- Activities Section -->
    <div>
        <h3 class="text-xl font-bold mb-4">Recent Activities</h3>
        <div class="space-y-4">
            <div class="bg-gray-800 p-4 rounded-lg">
                Joined "WEBB APPLICATION"
            </div>
            <div class="bg-gray-800 p-4 rounded-lg">
                Completed Task: ALGORITHM
            </div>
        </div>
    </div>
</div>


    <!-- Image Upload Modal -->
    <div id="uploadModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-gray-800 p-8 rounded-lg w-96">
            <h3 class="text-xl font-bold mb-4">Upload Profile Picture</h3>
            <form action="upload_profile_picture.php" method="POST" enctype="multipart/form-data">
                <input type="file" name="profile_picture" class="mb-4">
                <div class="flex justify-end gap-4">
                    <button type="button" onclick="closeUploadModal()" 
                            class="px-4 py-2 bg-gray-700 rounded hover:bg-gray-600">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 rounded hover:bg-blue-700">
                        Upload
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function hideProfilePage() {
    document.getElementById('profilePage').classList.add('hidden');
    // Optionally, show the main content if it was hidden
    document.querySelector('.flex-1').classList.remove('hidden');
}
        // Profile Dropdown Toggle
        const profileBtn = document.getElementById('profileBtn');
        const profileDropdown = document.getElementById('profileDropdown');
        
        profileBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            profileDropdown.classList.toggle('hidden');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', () => {
            profileDropdown.classList.add('hidden');
        });

        // Profile Page Navigation
        document.querySelectorAll('#profileDropdown button').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('profilePage').classList.remove('hidden');
                profileDropdown.classList.add('hidden');
            });
        });

        // Modal Functions
        function openUploadModal() {
            document.getElementById('uploadModal').classList.remove('hidden');
        }

        function closeUploadModal() {
            document.getElementById('uploadModal').classList.add('hidden');
        }

        // GSAP Animations for Sidebar Buttons
        gsap.from(".button-text", {
            opacity: 0,
            x: -20,
            stagger: 0.2,
            duration: 1,
            delay: 0.5,
            ease: "power2.out"
        });

        // GSAP Animations for Main Content
        gsap.from("h1", { opacity: 0, y: -20, duration: 1 });
        gsap.from(".bg-gray-800", { opacity: 0, y: 20, stagger: 0.1, duration: 1 });
    </script>
</body>
</html>