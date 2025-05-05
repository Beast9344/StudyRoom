<?php
require '../config/config.php';
require '../utils/auth.php';

// Regenerate session ID periodically
if (!isset($_SESSION['created'])) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
} elseif (time() - $_SESSION['created'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// Check authentication
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch user data using prepared statements
try {
    $user_id = $_SESSION['user'];
    $sql = "SELECT id, username, email, role, profile_picture FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows === 0) {
        session_destroy();
        header("Location: login.php");
        exit();
    }
    
    $stmt->bind_result($id, $username, $email, $role, $profile_picture);
    $stmt->fetch();
    $stmt->close();
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    die("Error loading user data");
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
$sql = "SELECT id, title, description, progress, status FROM tasks WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Fetch study rooms
$rooms = [];
$sql = "SELECT r.*, u.username as owner_name 
        FROM rooms r 
        JOIN users u ON r.owner_id = u.id 
        ORDER BY created_at DESC";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $rooms[] = $row;
}

// Fetch suggested rooms (rooms with available space)
$suggested_rooms = [];
$sql = "SELECT r.*, u.username as owner_name 
        FROM rooms r 
        JOIN users u ON r.owner_id = u.id 
        WHERE r.current_participants < r.participant_limit
        ORDER BY created_at DESC 
        LIMIT 5";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $suggested_rooms[] = $row;
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
    <style>
    html, body {
      margin: 0;
      padding: 0;
      height: 100%;
      overflow: hidden;
    }

    body {
      background: url('https://i.gifer.com/5Waz.gif') no-repeat center center fixed;
      background-size: cover;
    }
  </style>
</head>
<body class="bg-gray-900 text-white">

    <!-- Main Container -->
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-800 p-4 fixed top-0 left-0 h-screen z-50">
            <a href="index.php" class="cursor-pointer flex items-center space-x-2">
                <i class="fas fa-graduation-cap text-2xl text-blue-400"></i>
                <h1 class="text-xl font-bold">Study Room</h1>
            </a>
                <!-- Create Room -->
                <ul class="space-y-2">
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
            <a href="#" onclick="openTaskModal()" class="flex items-center p-3 rounded-lg group hover:bg-gray-700 transition-all">
                <i class="fas fa-tasks mr-3 text-gray-400 group-hover:text-blue-400"></i>
                <span class="text-gray-300 group-hover:text-white">My Tasks</span>
                <span class="ml-auto bg-gray-600 text-white px-2 py-1 rounded-full text-xs">1</span>
            </a>
        </li>
        <li>
            <a href="#" onclick="openNotesModal()" class="flex items-center p-3 rounded-lg group hover:bg-gray-700 transition-all">
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

        <li class="border-t border-gray-700 mt-4">
    <form action="logout.php" method="POST" class="w-full">
    <input 
    type="hidden" 
    name="csrf_token" 
    value="<?php echo isset($_SESSION['csrf_token']) ? htmlspecialchars($_SESSION['csrf_token']) : ''; ?>"
>
        <button type="submit" class="flex items-center w-full p-3 rounded-lg group hover:bg-red-600 transition-all duration-300">
            <i class="fas fa-sign-out-alt mr-3 text-lg text-red-300 group-hover:text-white"></i>
            <span class="text-gray-300 group-hover:text-white font-medium">Logout</span>
        </button>
    </form>
</li>
            </ul>
    </div>


<!-- Main Content -->
    <div class="flex-1 p-8 ml-64 overflow-y-auto">
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
                        <div id="profileDropdown" class="hidden absolute right-0 mt-2 w-48 bg-gray-800 rounded-lg shadow-xl z-50">
                            <div class="p-4 space-y-4">
                                <button onclick="showProfilePage()" 
                                    class="block w-full text-left hover:bg-gray-700 p-2 rounded">
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

                <div class="flex gap-4 mb-8">
                    <button class="bg-blue-600 px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                        Create Room
                    </button>
                    <button class="bg-gray-800 px-6 py-3 rounded-lg hover:bg-gray-700 transition">
                        Join Feature Room
                    </button>
                </div>
                <div class="space-y-6">
    <!-- Suggested Rooms -->
    <?php if(count($suggested_rooms) > 0): ?>
        <div class="mb-8">
            <h2 class="text-2xl mb-4">Suggested Rooms</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($suggested_rooms as $room): ?>
                    <div class="bg-gray-800 p-6 rounded-lg hover:bg-gray-750 transition">
                        <h3 class="text-xl font-bold"><?= htmlspecialchars($room['name']) ?></h3>
                        <p class="text-gray-400 mt-2"><?= htmlspecialchars($room['description']) ?></p>
                        <div class="flex items-center justify-between mt-4">
                            <span class="text-sm text-blue-400">
                                <?= $room['current_participants'] ?>/<?= $room['participant_limit'] ?> participants
                            </span>
                            <?php if ($room['current_participants'] < $room['participant_limit']): ?>
                                <a href="joinroom.php?id=<?= $room['id'] ?>" 
                                   class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded text-sm">
                                    Join Room
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- All Rooms -->
    <h2 class="text-2xl mb-4">All Study Rooms</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php foreach ($rooms as $room): ?>
            <div class="bg-gray-800 p-6 rounded-lg hover:bg-gray-750 transition">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold"><?= htmlspecialchars($room['name']) ?></h3>
                    <span class="text-sm text-gray-400">
                        <?= date('M j, Y', strtotime($room['created_at'])) ?>
                    </span>
                </div>
                <p class="text-gray-400 mt-2"><?= htmlspecialchars($room['description']) ?></p>
                <div class="flex items-center justify-between mt-4">
                    <div class="flex items-center">
                        <span class="text-sm text-blue-400 mr-4">
                            Owner: <?= htmlspecialchars($room['owner_name']) ?>
                        </span>
                        <span class="text-sm text-gray-400">
                            <?= $room['current_participants'] ?>/<?= $room['participant_limit'] ?> participants
                        </span>
                    </div>
                    <?php if ($room['current_participants'] < $room['participant_limit']): ?>
                        <a href="joinroom.php?id=<?= $room['id'] ?>" 
                           class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded text-sm">
                            Join
                        </a>
                    <?php else: ?>
                        <span class="text-sm text-red-400">Full</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
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

<!-- Notes Modal -->
<div id="notesModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg w-full max-w-2xl max-h-[90vh] flex flex-col">
        <!-- Modal Header -->
        <div class="flex justify-between items-center p-4 border-b border-gray-700">
            <h3 class="text-xl font-bold">
                <i class="fas fa-book mr-2"></i>Study Notes
            </h3>
            <button onclick="closeNotesModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- Modal Content -->
        <div class="flex-1 overflow-y-auto p-4">
            <!-- Note Editor -->
            <div class="mb-4">
                <input type="text" id="noteTitle" 
                       class="w-full mb-2 p-2 bg-gray-700 rounded" 
                       placeholder="Note Title">
                <textarea id="noteContent" 
                          class="w-full h-64 p-3 bg-gray-700 rounded-lg font-mono text-sm"
                          placeholder="Write your notes here..."></textarea>
            </div>
            
            <!-- Saved Notes -->
            <div id="savedNotes" class="space-y-3">
                <!-- Notes will be loaded here -->
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="flex justify-between items-center p-4 border-t border-gray-700">
            <div class="flex gap-2">
                <select id="noteCategory" class="bg-gray-700 rounded px-2 py-1 text-sm">
                    <option value="study">📚 Study Note</option>
                    <option value="important">❗ Important Note</option>
                    <option value="personal">🔒 Personal Note</option>
                    <option value="meeting">👥 Meeting Notes</option>
                </select>
                <button onclick="saveNote()" 
                        class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded">
                    <i class="fas fa-save mr-2"></i>Save
                </button>
            </div>
            <button onclick="closeNotesModal()" 
                    class="bg-gray-700 hover:bg-gray-600 px-4 py-2 rounded">
                Close
            </button>
        </div>
    </div>
</div>



<!-- Task Modal -->
<div id="taskModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg w-full max-w-2xl max-h-[90vh] flex flex-col">
        <!-- Modal Header -->
        <div class="flex justify-between items-center p-4 border-b border-gray-700">
            <h3 class="text-xl font-bold">
                <i class="fas fa-tasks mr-2 text-blue-400"></i>Task Manager
            </h3>
            <button onclick="closeTaskModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- Modal Content -->
        <div class="flex-1 overflow-y-auto p-4">
            <!-- Task Form -->
            <div class="mb-4 space-y-4">
                <input type="text" id="taskTitle" 
                       class="w-full p-2 bg-gray-700 rounded" 
                       placeholder="Task Title">
                
                <textarea id="taskDescription" 
                          class="w-full h-32 p-3 bg-gray-700 rounded-lg text-sm"
                          placeholder="Task Description..."></textarea>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm mb-2">Deadline</label>
                        <input type="datetime-local" id="taskDeadline" 
                               class="w-full p-2 bg-gray-700 rounded">
                    </div>
                    <div>
                        <label class="block text-sm mb-2">Priority</label>
                        <select id="taskPriority" class="w-full p-2 bg-gray-700 rounded">
                            <option value="low">⭐ Low</option>
                            <option value="medium">⭐⭐ Medium</option>
                            <option value="high">⭐⭐⭐ High</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm mb-2">Progress</label>
                        <div class="flex items-center gap-2">
                            <input type="range" id="taskProgress" min="0" max="100" value="0" 
                                   class="w-full bg-gray-700 rounded-lg appearance-none h-2">
                            <span id="progressValue" class="text-sm">0%</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm mb-2">Status</label>
                        <select id="taskStatus" class="w-full p-2 bg-gray-700 rounded">
                            <option value="not_started">🔴 Not Started</option>
                            <option value="in_progress">🟡 In Progress</option>
                            <option value="completed">🟢 Completed</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Task List -->
            <div id="taskList" class="space-y-3">
                <!-- Tasks will be loaded here -->
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="flex justify-between items-center p-4 border-t border-gray-700">
            <div class="flex gap-2">
                <button onclick="saveTask()" 
                        class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded flex items-center">
                    <i class="fas fa-save mr-2"></i>Save Task
                </button>
                <button onclick="clearTaskForm()" 
                        class="bg-gray-700 hover:bg-gray-600 px-4 py-2 rounded">
                    Clear
                </button>
            </div>
            <button onclick="closeTaskModal()" 
                    class="bg-gray-700 hover:bg-gray-600 px-4 py-2 rounded">
                Close
            </button>
        </div>
    </div>
</div>

<!-- Profile Page (Hidden by default) -->
<div id="profilePage" class="hidden fixed top-0 right-0 w-96 h-screen bg-gray-900 p-8 border-l border-gray-700 z-50 overflow-y-auto">
    <!-- Back Button -->
    <button onclick="hideProfilePage()" class="mb-4 p-2 bg-gray-700 rounded hover:bg-gray-600">
        ← Back to Dashboard
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


//notes
let currentNoteId = null;

function openNotesModal() {
    document.getElementById('notesModal').classList.remove('hidden');
    loadNotes();
}

function closeNotesModal() {
    document.getElementById('notesModal').classList.add('hidden');
    resetNoteForm();
}

function resetNoteForm() {
    currentNoteId = null;
    document.getElementById('noteTitle').value = '';
    document.getElementById('noteContent').value = '';
    document.getElementById('noteCategory').value = 'study';
}

async function loadNotes() {
    try {
        const response = await fetch('fetchnotes.php');
        const notes = await response.json();
        
        const notesContainer = document.getElementById('savedNotes');
        notesContainer.innerHTML = '';
        
        notes.forEach(note => {
            const noteElement = document.createElement('div');
            noteElement.className = 'bg-gray-700 p-3 rounded-lg cursor-pointer hover:bg-gray-600';
            noteElement.innerHTML = `
                <div class="flex justify-between items-start">
                    <div>
                        <h4 class="font-bold">${note.title}</h4>
                        <p class="text-sm text-gray-400 mt-1">${note.content.substring(0, 50)}...</p>
                        <span class="text-xs ${getCategoryColor(note.category)}">
                            ${getCategoryLabel(note.category)}
                        </span>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="editNote(${note.id}, '${note.title}', '${note.content}', '${note.category}')" 
                                class="text-blue-400 hover:text-blue-300">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteNote(${note.id})" 
                                class="text-red-400 hover:text-red-300">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            notesContainer.appendChild(noteElement);
        });
    } catch (error) {
        console.error('Error loading notes:', error);
    }
}

function getCategoryColor(category) {
    const colors = {
        study: 'text-blue-400',
        important: 'text-red-400',
        personal: 'text-purple-400',
        meeting: 'text-green-400'
    };
    return colors[category] || 'text-gray-400';
}

function getCategoryLabel(category) {
    const labels = {
        study: '📚 Study Note',
        important: '❗ Important Note',
        personal: '🔒 Personal Note',
        meeting: '👥 Meeting Notes'
    };
    return labels[category] || 'Note';
}

async function saveNote() {
    const noteData = {
        id: currentNoteId,
        title: document.getElementById('noteTitle').value,
        content: document.getElementById('noteContent').value,
        category: document.getElementById('noteCategory').value
    };

    try {
        const response = await fetch('savenote.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(noteData)
        });

        if (response.ok) {
            loadNotes();
            resetNoteForm();
        }
    } catch (error) {
        console.error('Error saving note:', error);
    }
}

function editNote(id, title, content, category) {
    currentNoteId = id;
    document.getElementById('noteTitle').value = title;
    document.getElementById('noteContent').value = content;
    document.getElementById('noteCategory').value = category;
}

async function deleteNote(id) {
    if (confirm('Are you sure you want to delete this note?')) {
        try {
            await fetch(`deletenote.php?id=${id}`);
            loadNotes();
        } catch (error) {
            console.error('Error deleting note:', error);
        }
    }
}
////////////////////////////////////////

// Task Management Script
let currentTaskId = null;

function openTaskModal() {
    document.getElementById('taskModal').classList.remove('hidden');
    loadTasks();
}

function closeTaskModal() {
    document.getElementById('taskModal').classList.add('hidden');
    clearTaskForm();
}

function clearTaskForm() {
    currentTaskId = null;
    document.getElementById('taskTitle').value = '';
    document.getElementById('taskDescription').value = '';
    document.getElementById('taskDeadline').value = '';
    document.getElementById('taskPriority').value = 'low';
    document.getElementById('taskProgress').value = 0;
    document.getElementById('progressValue').textContent = '0%';
    document.getElementById('taskStatus').value = 'not_started';
}

// Update progress value display
document.getElementById('taskProgress').addEventListener('input', (e) => {
    document.getElementById('progressValue').textContent = `${e.target.value}%`;
});

async function loadTasks() {
    try {
        const response = await fetch('fetchtasks.php');
        const tasks = await response.json();
        const taskList = document.getElementById('taskList');
        taskList.innerHTML = '';

        tasks.forEach(task => {
            const taskElement = document.createElement('div');
            taskElement.className = 'bg-gray-700 p-3 rounded-lg cursor-pointer hover:bg-gray-600';
            taskElement.innerHTML = `
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="font-bold">${task.title}</span>
                            <span class="text-xs ${getPriorityClass(task.priority)}">
                                ${task.priority.toUpperCase()}
                            </span>
                        </div>
                        <div class="text-sm text-gray-400 mb-2">${task.description}</div>
                        
                        <div class="flex items-center justify-between text-xs">
                            <div>
                                <span class="mr-4">📅 ${new Date(task.deadline).toLocaleDateString()}</span>
                                <span>${getStatusIcon(task.status)} ${formatStatus(task.status)}</span>
                            </div>
                            <div class="w-24">
                                <div class="h-2 bg-gray-600 rounded-full">
                                    <div class="h-2 bg-blue-500 rounded-full" 
                                         style="width: ${task.progress}%"></div>
                                </div>
                                <span class="text-right block">${task.progress}%</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-2 ml-4">
                        <button onclick="editTask('${task.id}', '${escapeQuotes(task.title)}', '${escapeQuotes(task.description)}', 
                            '${task.deadline}', '${task.priority}', ${task.progress}, '${task.status}')" 
                                class="text-blue-400 hover:text-blue-300">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteTask('${task.id}')" 
                                class="text-red-400 hover:text-red-300">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            taskList.appendChild(taskElement);
        });
    } catch (error) {
        console.error('Error loading tasks:', error);
    }
}

function getPriorityClass(priority) {
    const classes = {
        high: 'text-red-400',
        medium: 'text-yellow-400',
        low: 'text-gray-400'
    };
    return classes[priority] || 'text-gray-400';
}

function getStatusIcon(status) {
    const icons = {
        completed: '🟢',
        in_progress: '🟡',
        not_started: '🔴'
    };
    return icons[status] || '⚪';
}

function formatStatus(status) {
    return status.replace(/_/g, ' ').toUpperCase();
}

function escapeQuotes(str) {
    return str.replace(/'/g, "\\'");
}

async function saveTask() {
    const taskData = {
        id: currentTaskId,
        title: document.getElementById('taskTitle').value,
        description: document.getElementById('taskDescription').value,
        deadline: document.getElementById('taskDeadline').value,
        priority: document.getElementById('taskPriority').value,
        progress: document.getElementById('taskProgress').value,
        status: document.getElementById('taskStatus').value
    };

    try {
        const response = await fetch('savetask.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(taskData)
        });

        if (response.ok) {
            loadTasks();
            clearTaskForm();
        }
    } catch (error) {
        console.error('Error saving task:', error);
    }
}

function editTask(id, title, description, deadline, priority, progress, status) {
    currentTaskId = id;
    document.getElementById('taskTitle').value = title;
    document.getElementById('taskDescription').value = description;
    document.getElementById('taskDeadline').value = deadline.slice(0, 16);
    document.getElementById('taskPriority').value = priority;
    document.getElementById('taskProgress').value = progress;
    document.getElementById('progressValue').textContent = `${progress}%`;
    document.getElementById('taskStatus').value = status;
}

async function deleteTask(id) {
    if (confirm('Are you sure you want to delete this task?')) {
        try {
            await fetch(`deletetask.php?id=${id}`);
            loadTasks();
        } catch (error) {
            console.error('Error deleting task:', error);
        }
    }
}

////////////////////

function showProfilePage() {
    document.getElementById('profilePage').classList.remove('hidden');
    document.querySelector('.flex-1').classList.add('hidden'); // Hide main content
    document.getElementById('profileDropdown').classList.add('hidden'); // Close dropdown
}

function hideProfilePage() {
    document.getElementById('profilePage').classList.add('hidden'); // Use standard quotes
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