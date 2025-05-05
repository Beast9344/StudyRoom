<?php
require '../config/config.php';
require '../utils/auth.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $participant_limit = min(20, intval($_POST['participant_limit'])); // Max 20
    $owner_id = $_SESSION['user']['id'];

    try {
        // Start transaction
        $conn->begin_transaction();

        // Create room
        $stmt = $conn->prepare("INSERT INTO rooms (name, description, owner_id, participant_limit) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssii", $name, $description, $owner_id, $participant_limit);
        $stmt->execute();
        
        $room_id = $stmt->insert_id;

        // Add owner as participant
        $stmt = $conn->prepare("INSERT INTO room_participants (room_id, user_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $room_id, $owner_id);
        $stmt->execute();

        $conn->commit();
        header("Location: dashboard.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error creating room: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <meta charset="UTF-8">
   <title>User Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://d3js.org/d3.v7.min.js"></script>
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-900 text-white">
    <!-- Main Container -->
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-800 p-4">
        <i class="fas fa-graduation-cap text-2xl text-blue-400"></i>
            <h1 class="text-xl font-bold mb-6">Study Room</h1>
            <ul class="space-y-2">
                <!-- Create Room -->
                <ul class="space-y-2">
        <!-- Create Room -->
            <li>
                <a href="createroom.php" class="flex items-center p-3 rounded-lg group hover:bg-blue-600 transition-all duration-300">
                    <i class="fas fa-plus-circle mr-3 text-lg text-blue-300 group-hover:text-white"></i>
                    <span class="text-gray-300 group-hover:text-white font-medium">Create Room</span>
                    <span class="ml-auto bg-blue-500 text-white px-2 py-1 rounded-full text-xs animate-pulse">NEW</span>
                </a>
            </li>

                <!-- Divider -->
                <li class="border-t border-gray-700 my-4"></li>


                <!-- Navigation Items -->
        <li>
            <a href="#" class="flex items-center p-3 rounded-lg group hover:bg-gray-700 transition-all">
                <i class="fas fa-tasks mr-3 text-gray-400 group-hover:text-blue-400"></i>
                <span class="text-gray-300 group-hover:text-white">My Tasks</span>
                <span class="ml-auto bg-gray-600 text-white px-2 py-1 rounded-full text-xs">1</span>
            </a>
        </li>
        <li>
            <a href="#" class="flex items-center p-3 rounded-lg group hover:bg-gray-700 transition-all">
                <i class="fas fa-tasks mr-3 text-gray-400 group-hover:text-blue-400"></i>
                <span class="text-gray-300 group-hover:text-white">Notes</span>
                <span class="ml-auto bg-gray-600 text-white px-2 py-1 rounded-full text-xs">4</span>
            </a>
        </li>

        <li>
            <a href="#" class="flex items-center p-3 rounded-lg group hover:bg-gray-700 transition-all">
                <i class="fas fa-comments mr-3 text-gray-400 group-hover:text-green-400"></i>
                <span class="text-gray-300 group-hover:text-white">Group Chat</span>
                <span class="ml-auto bg-green-500 text-white px-2 py-1 rounded-full text-xs">3</span>
            </a>
        </li>

        <li>
            <a href="#" class="flex items-center p-3 rounded-lg group hover:bg-gray-700 transition-all">
                <i class="fas fa-users mr-3 text-gray-400 group-hover:text-purple-400"></i>
                <span class="text-gray-300 group-hover:text-white">Collaborators</span>
            </a>
        </li>

        <li>
            <a href="marks.php" class="flex items-center p-3 rounded-lg group hover:bg-gray-700 transition-all">
                <i class="fas fa-star mr-3 text-gray-400 group-hover:text-yellow-400"></i>
                <span class="text-gray-300 group-hover:text-white">Marks</span>
            </a>
        </li>
        <a href="#" class="flex items-center p-3 rounded-lg group hover:bg-gray-700 transition-all">
                <i class="fas fa-video mr-3 text-gray-400 group-hover:text-red-400"></i>
                <span class="text-gray-300 group-hover:text-white">Live Sessions</span>
            </a>
        </li>

        <!-- Divider -->
        <li class="border-t border-gray-700 my-4"></li>
                <!-- Teachers Zone -->
                <li>
            <a href="#" class="flex items-center p-3 rounded-lg group hover:bg-purple-800 transition-all">
                <i class="fas fa-chalkboard-teacher mr-3 text-purple-400"></i>
                <span class="text-purple-300 font-medium">Teacher Zone</span>
                <i class="fas fa-lock ml-2 text-purple-400 text-sm"></i>
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
            
    <div class="flex-1 p-8">
        <div class="max-w-2xl mx-auto">
            <h2 class="text-2xl mb-6">Create New Study Room</h2>
            
            <?php if(isset($error)): ?>
                <div class="bg-red-800 p-4 mb-4 rounded-lg"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="block mb-2">Room Name</label>
                    <input type="text" name="name" required class="w-full p-2 bg-gray-800 rounded">
                </div>
                
                <div>
                    <label class="block mb-2">Description</label>
                    <textarea name="description" rows="4" class="w-full p-2 bg-gray-800 rounded"></textarea>
                </div>
                
                <div>
                    <label class="block mb-2">Maximum Participants (max 20)</label>
                    <input type="number" name="participant_limit" min="2" max="20" value="20" 
                           class="w-full p-2 bg-gray-800 rounded">
                </div>
                
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 px-6 py-2 rounded">
                    Create Room
                </button>
            </form>
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
<!-- Marks Section -->
<div id="marksSection" class="hidden w-full p-8">
    <h2 class="text-2xl mb-4">Marks</h2>
    <div id="marksTable"></div>
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