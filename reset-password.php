<?php
session_start();
$token = $_GET['token'] ?? '';
$error = $_SESSION['reset_error'] ?? '';
unset($_SESSION['reset_error']);
?>
<!DOCTYPE html>
<html data-theme="light" lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Logistics 2</title>
    <link rel="shortcut icon" href="images/logo/favicon.ico" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.4.19/dist/full.min.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="./css/style.css">
</head>

<body class="flex items-center justify-center min-h-screen bg-gradient-to-r from-slate-700 to-slate-900">

    <div class="card w-96 shadow-xl bg-white">
        <div class="bg-[#001f54] text-white py-6 px-6 text-center w-full rounded-t-md border-b-2 border-solid border-yellow-700">
            <h1 class="card-title justify-center mb-2">Reset Password</h1>
            <p class="text-sm text-gray-300">Enter a new password for your account</p>
        </div>

        <div class="card-body flex flex-col gap-2 justify-center">
            <?php if (!empty($error)): ?>
                <p class="text-center mb-3 text-red-600"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>

            <form method="POST" action="includes/process_reset.php?token=<?php echo urlencode($token); ?>">
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">New Password</span>
                    </label>
                    <input type="password" class="input input-bordered w-full" name="password" required />
                </div>

                <div class="form-control mt-3">
                    <label class="label">
                        <span class="label-text">Confirm Password</span>
                    </label>
                    <input type="password" class="input input-bordered w-full" name="confirm" required />
                </div>

                <div class="form-control mt-6">
                    <button type="submit" class="btn btn-primary w-full">
                        <i data-lucide="lock-reset" class="w-5 h-5"></i>
                        Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script>
        lucide.createIcons();
    </script>
</body>

</html>