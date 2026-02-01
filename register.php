<?php
session_start();

// If already logged in, redirect to profile
if (isset($_SESSION['user'])) {
    header('Location: profile.php');
    exit;
}

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    // Load existing users
    $usersFile = __DIR__ . '/users.json';
    $users = file_exists($usersFile) ? json_decode(file_get_contents($usersFile), true) : [];

    // Validation
    if (empty($username)) {
        $error = 'Username is required!';
    } elseif (strpos($username, ' ') !== false) {
        $error = 'Username cannot contain spaces!';
    } elseif (isset($users[$username])) {
        $error = 'Username already exists!';
    } elseif (empty($email)) {
        $error = 'Email is required!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address!';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters!';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match!';
    } else {
        // Check if email already exists
        foreach ($users as $existingUser) {
            if (isset($existingUser['email']) && strtolower($existingUser['email']) === strtolower($email)) {
                $error = 'Email already registered!';
                break;
            }
        }

        if (empty($error)) {
            $profilePic = '';

            // Handle profile picture upload
            if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $extension = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));

                if (!in_array($extension, $allowed)) {
                    $error = 'Only JPG, PNG, GIF allowed!';
                } elseif ($_FILES['profile_pic']['size'] > 2097152) {
                    $error = 'File too large! Max 2MB.';
                } else {
                    $profilePic = $username . '_' . time() . '.' . $extension;
                    if (!move_uploaded_file($_FILES['profile_pic']['tmp_name'], __DIR__ . '/uploads/' . $profilePic)) {
                        $error = 'Failed to save profile picture. Check folder permissions.';
                    }
                }
            }

            // If no error, save user
            if (empty($error)) {
                $users[$username] = [
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'email' => $email,
                    'profile_pic' => $profilePic,
                    'created_at' => date('Y-m-d')
                ];
                file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));

                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Registration successful! Please login.'];
                header('Location: login.php');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <style>
        body {
            font-family: Arial;
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            box-sizing: border-box;
        }
        input[type="file"] {
            margin: 10px 0;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #28a745;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background: #218838;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-top: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <h1>Register</h1>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="text" name="username" placeholder="Username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
        <input type="email" name="email" placeholder="Email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        <input type="password" name="password" placeholder="Password (min 6 chars)" required>
        <input type="password" name="confirm" placeholder="Confirm Password" required>
        <label>Profile Picture (optional):</label>
        <input type="file" name="profile_pic" accept="image/*">
        <button type="submit">Register</button>
    </form>

    <p>Already have an account? <a href="login.php">Login</a></p>
</body>
</html>
