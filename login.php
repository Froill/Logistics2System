<?php
session_start();

// Redirect to home if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Retain input and show error message
$username = $_SESSION['username'] ?? ''; // Retain username from session if set
$error = $_SESSION['login_error'] ?? ''; // Show any login errors
unset($_SESSION['login_error']); // Clear error after display
unset($_SESSION['username']); // Clear username after using it
?>

<!DOCTYPE html>
<html data-theme="light" lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Logistics 2</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.4.19/dist/full.min.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="./css/style.css">


</head>

<body class=" flex items-center justify-center min-h-screen bg-gradient-to-r from-slate-700 to-slate-900">

    <div class="card w-96 shadow-xl bg-white ">
        <div class="bg-[#001f54] text-white py-8 w-full rounded-t-md border-b-2 border-solid border-yellow-700">
            <img src="images/logo/sonly.png"
                class="w-auto h-24 rounded-full border-solid border-yellow-600 border-4 mx-auto mb-4 bg-[#001f54]"
                alt="Logo">
            <h2 class="card-title justify-center">Login to your account</h2>
        </div>
        <div class="card-body flex flex-col gap-3 justify-center">

            <?php if (!empty($error)):  ?>
                <div class="alert alert-error flex flex-row -center shadow-lg">
                    <i data-lucide="octagon-alert"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            <form action="includes/validate_login.php" method="POST">
                <div class="form-control w-full max-w-xs mb-3">
                    <label class="label flex items-center justify-start gap-2">
                        <i data-lucide="user" class="w-5 h-5 "></i>
                        <span class="label-text">Username</span>
                    </label>
                    <input
                        type="text"
                        name="username"
                        placeholder="Enter username"
                        value="<?php echo htmlspecialchars($username); ?>"
                        class="input input-bordered w-full"
                        required />
                </div>


                <div class="form-control mt-2 w-full max-w-xs mb-6">
                    <label class="label flex items-center justify-start gap-2">
                        <i data-lucide="lock" class="w-5 h-5"></i>
                        <span class="label-text">Password</span>
                    </label>
                    <input
                        type="password"
                        name="password"
                        placeholder="Enter password"
                        class="input input-bordered w-full"
                        required />
                </div>


                <div class="form-control mt-4">
                    <button type="submit" class="btn btn-primary"><i data-lucide="log-in" class="w-5 h-5"></i>Sign-in</button>
                </div>
            </form>
        </div>
    </div>
    <script src=" https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script>
        lucide.createIcons();
    </script>
</body>

</html>