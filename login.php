<?php
session_start();

$usersFile = __DIR__ . '/users.json';
$users = file_exists($usersFile) ? json_decode(file_get_contents($usersFile), true) : [];

// Check for "Remember Me" cookie
if (!isset($_SESSION['user']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    foreach ($users as $username => $userData) {
        if (isset($userData['remember_token']) && $userData['remember_token'] === $token) {
            $_SESSION['user'] = $username;
            header('Location: profile.php');
            exit;
        }
    }
    // Invalid token, clear the cookie
    setcookie('remember_token', '', time() - 3600, '/');
}

// If already logged in, redirect to profile
if (isset($_SESSION['user'])) {
    header('Location: profile.php');
    exit;
}

$error = '';

// Get flash message
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    // Reload users (in case of concurrent changes)
    $users = file_exists($usersFile) ? json_decode(file_get_contents($usersFile), true) : [];

    // Check credentials
    if (isset($users[$username]) && password_verify($password, $users[$username]['password'])) {
        $_SESSION['user'] = $username;

        // Handle "Remember Me"
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $users[$username]['remember_token'] = $token;
            file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
            setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/'); // 30 days
        }

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Welcome back, ' . $username . '!'];
        header('Location: profile.php');
        exit;
    } else {
        $error = 'Invalid username or password!';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <style>
        body {
            font-family: Arial;
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background: #0056b3;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .remember-row {
            display: flex;
            align-items: center;
            margin: 10px 0;
        }
        .remember-row input {
            width: auto;
            margin-right: 8px;
        }
        .remember-row label {
            margin: 0;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <h1>Login</h1>

    <?php if ($flash): ?>
        <div class="<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <div class="remember-row">
            <input type="checkbox" name="remember" id="remember">
            <label for="remember">Remember Me</label>
        </div>
        <button type="submit">Login</button>
    </form>

    <p>Don't have an account? <a href="register.php">Register</a></p>
</body>
</html>
