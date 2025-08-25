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
$reset_success = $_SESSION['reset_success'] ?? ''; // Show reset success message    
unset($_SESSION['login_error']); // Clear error after display
unset($_SESSION['reset_success']); // Clear reset success after display
unset($_SESSION['username']); // Clear username after using it
?>

<!DOCTYPE html>
<html data-theme="light" lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - Logistics 2</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.4.19/dist/full.min.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="./css/style.css">
</head>

<body class="flex items-center justify-center min-h-screen bg-gradient-to-r from-slate-700 to-slate-900">

    <div class="card w-80 md:w-96 shadow-xl bg-white ">
        <div class="bg-[#001f54] text-white py-8 w-full rounded-t-md border-b-2 border-solid border-yellow-700">
            <img src="images/logo/sonly.png"
                class="size-20 rounded-full border-solid border-yellow-600 border-4 mx-auto mb-2 bg-[#001f54]"
                alt="Logo">
            <h2 class="card-title justify-center">Login to your account</h2>
        </div>
        <div class="card-body flex flex-col gap-3 justify-center">

            <?php if (!empty($error)):  ?>
                <div class="alert alert-error flex flex-row shadow-lg">
                    <i data-lucide="octagon-alert"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($reset_success)):  ?>
                <div class="alert alert-success alert-soft flex flex-row gap-2 shadow-lg">
                    <i data-lucide="shield-check"></i>
                    <span><?php echo htmlspecialchars($reset_success); ?></span>
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

                <!-- reCAPTCHA v2 widget -->
                <div class="flex justify-center w-full">
                    <div class="g-recaptcha" data-sitekey="6Lf6lrArAAAAAHAIJbwW50D8q4on5jwB-2MRw_Ho"></div>
                </div>
                <script src="https://www.google.com/recaptcha/api.js" async defer></script>

                <div class="form-control mt-4">
                    <button type="submit" class="btn btn-primary"><i data-lucide="log-in" class="w-5 h-5"></i>Sign-in</button>
                </div>
                <div class="mt-3 text-center">
                    <label onclick="reset_password.showModal()" class="text-blue-700 hover:underline cursor-pointer text-sm">Forgot password?</label>
                </div>
        </div>
    </div>

    <dialog id="reset_password" class="modal">
        <div class="modal-box">
            <form method="dialog">
                <button onclick="reset_password.close()" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
            </form>

            <h3 class="text-lg font-bold mb-4 text-center">Reset Your Password</h3>

            <form method="POST" action="includes/process_forgot.php" id="forgotForm" class="flex flex-col items-center gap-3">
                <div class="form-control w-full">
                    <label class="label flex items-center justify-start gap-2">
                        <i data-lucide="mail"></i>
                        <span class="label-text">Email</span>
                    </label>
                    <input type="email" name="email" placeholder="Enter your email" class="input input-bordered w-full" required />
                </div>

                <div class="flex justify-center w-full">
                    <button type="submit" id="resetBtn" class="btn btn-primary btn-outline  mt-4"> <i data-lucide="refresh-ccw" class="w-5 h-5"></i>Send Reset Link</button>
                </div>
            </form>
        </div>
    </dialog>



    <script src=" https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script>
        lucide.createIcons();

        const forgotForm = document.getElementById('forgotForm');
        forgotForm.addEventListener('submit', function(event) {
            const resetBtn = document.getElementById('resetBtn');
            const loading = ['loading', 'loading-spinner']
            resetBtn.classList.add(...loading)
        });
    </script>

</body>

</html>