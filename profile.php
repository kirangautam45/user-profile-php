<?php
session_start();

// Check if logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['user'];

// Load user data
$usersFile = __DIR__ . '/users.json';
$users = json_decode(file_get_contents($usersFile), true);
$user = $users[$username];

$error = '';
$success = '';

// Handle profile info update (email and username)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_info'])) {
    $newEmail = trim($_POST['email'] ?? '');
    $newUsername = trim($_POST['new_username'] ?? '');

    if (empty($newEmail) || empty($newUsername)) {
        $error = 'Email and username are required.';
    } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $newUsername)) {
        $error = 'Username can only contain letters, numbers, and underscores.';
    } elseif ($newUsername !== $username && isset($users[$newUsername])) {
        $error = 'Username already taken.';
    } else {
        // Update email
        $users[$username]['email'] = $newEmail;

        // Handle username change
        if ($newUsername !== $username) {
            $users[$newUsername] = $users[$username];
            unset($users[$username]);
            $_SESSION['user'] = $newUsername;
            $username = $newUsername;
        }

        file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
        $user = $users[$username];

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Profile updated successfully!'];
        header('Location: profile.php');
        exit;
    }
}

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $extension = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $allowed)) {
            $error = 'Only JPG, PNG, GIF allowed!';
        } elseif ($_FILES['profile_pic']['size'] > 2097152) {
            $error = 'File too large! Max 2MB.';
        } else {
            // Delete old profile picture if exists
            if (!empty($user['profile_pic'])) {
                $oldPicPath = __DIR__ . '/uploads/' . $user['profile_pic'];
                if (file_exists($oldPicPath)) {
                    unlink($oldPicPath);
                }
            }

            // Save new profile picture
            $newPic = $username . '_' . time() . '.' . $extension;
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], __DIR__ . '/uploads/' . $newPic)) {
                // Update user data
                $users[$username]['profile_pic'] = $newPic;
                file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
                $user = $users[$username];

                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Profile picture updated successfully!'];
                header('Location: profile.php');
                exit;
            } else {
                $error = 'Failed to move uploaded file. Check folder permissions.';
            }
        }
    } else {
        // Handle specific error codes
        switch ($_FILES['profile_pic']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $error = 'File too large! Max 2MB.';
                break;
            case UPLOAD_ERR_PARTIAL:
                $error = 'File was only partially uploaded.';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $error = 'Missing a temporary folder.';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $error = 'Failed to write file to disk.';
                break;
            case UPLOAD_ERR_EXTENSION:
                $error = 'A PHP extension stopped the file upload.';
                break;
            default:
                $error = 'Error uploading file. Error code: ' . $_FILES['profile_pic']['error'];
                break;
        }
    }
}

// Get flash message
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Profile - <?= htmlspecialchars($username) ?></title>
    <style>
        body {
            font-family: Arial;
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
        }
        .profile-card {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
        }
        .profile-pic {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #007bff;
        }
        .no-pic {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            color: #666;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            text-decoration: none;
            border-radius: 5px;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-primary { background: #007bff; }
        .btn-primary:hover { background: #0056b3; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #545b62; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .info {
            margin: 20px 0;
            color: #666;
        }
        .info p {
            margin: 8px 0;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .upload-form {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .upload-form input[type="file"] {
            margin: 10px 0;
        }
        .upload-form h3 {
            margin: 0 0 15px 0;
            font-size: 16px;
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .form-group input:focus {
            outline: none;
            border-color: #007bff;
        }
        .actions {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="profile-card">
        <h1>My Profile</h1>

        <?php if ($flash): ?>
            <div class="<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($user['profile_pic'])): ?>
            <img src="uploads/<?= htmlspecialchars($user['profile_pic']) ?>" class="profile-pic" alt="Profile Picture">
        <?php else: ?>
            <div class="no-pic">No Photo</div>
        <?php endif; ?>

        <h2><?= htmlspecialchars($username) ?></h2>

        <div class="info">
            <?php if (!empty($user['email'])): ?>
                <p>Email: <?= htmlspecialchars($user['email']) ?></p>
            <?php endif; ?>
            <p>Member since: <?= htmlspecialchars($user['created_at']) ?></p>
        </div>

        <form method="POST" class="upload-form">
            <h3>Edit Profile</h3>
            <div class="form-group">
                <label>Username:</label>
                <input type="text" name="new_username" value="<?= htmlspecialchars($username) ?>" required>
            </div>
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
            </div>
            <button type="submit" name="update_info" class="btn btn-primary">Save Changes</button>
        </form>

        <form method="POST" enctype="multipart/form-data" class="upload-form">
            <h3>Profile Picture</h3>
            <input type="file" name="profile_pic" accept="image/*" required>
            <br>
            <small class="text-muted">Max upload size: <?= ini_get('upload_max_filesize') ?></small>
            <br>
            <button type="submit" class="btn btn-secondary">Upload New Picture</button>
        </form>

        <div class="actions">
            <a href="change-password.php" class="btn btn-primary">Change Password</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>
</body>
</html>
