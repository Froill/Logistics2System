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
            <h1 class="card-title justify-center mb-2 text-lg text-gray-200">
                <i data-lucide="key" class="size-6"></i>
                Create a strong new password
            </h1>
            <ul class="list-disc text-sm text-gray-200 mt-3 space-y-1">
                <li id="tip-length" class="flex items-center gap-2">
                    <span class="inline-flex w-4 h-4 items-center justify-center">
                        <i data-lucide="circle-x" class="icon-x w-4 h-4 text-red-300 hidden"></i>
                        <i data-lucide="circle-check" class="icon-check w-4 h-4 text-green-400 hidden"></i>
                    </span>
                    <span>At least 8 characters long</span>
                </li>
                <li id="tip-uppercase" class="flex items-center gap-2">
                    <span class="inline-flex w-4 h-4 items-center justify-center">
                        <i data-lucide="circle-x" class="icon-x w-4 h-4 text-red-300 hidden"></i>
                        <i data-lucide="circle-check" class="icon-check w-4 h-4 text-green-400 hidden"></i>
                    </span>
                    <span>At least one uppercase letter</span>
                </li>
                <li id="tip-lowercase" class="flex items-center gap-2">
                    <span class="inline-flex w-4 h-4 items-center justify-center">
                        <i data-lucide="circle-x" class="icon-x w-4 h-4 text-red-300 hidden"></i>
                        <i data-lucide="circle-check" class="icon-check w-4 h-4 text-green-400 hidden"></i>
                    </span>
                    <span>At least one lowercase letter</span>
                </li>
                <li id="tip-number" class="flex items-center gap-2">
                    <span class="inline-flex w-4 h-4 items-center justify-center">
                        <i data-lucide="circle-x" class="icon-x w-4 h-4 text-red-300 hidden"></i>
                        <i data-lucide="circle-check" class="icon-check w-4 h-4 text-green-400 hidden"></i>
                    </span>
                    <span>At least one number</span>
                </li>
                <li id="tip-symbol" class="flex items-center gap-2">
                    <span class="inline-flex w-4 h-4 items-center justify-center">
                        <i data-lucide="circle-x" class="icon-x w-4 h-4 text-red-300 hidden"></i>
                        <i data-lucide="circle-check" class="icon-check w-4 h-4 text-green-400 hidden"></i>
                    </span>
                    <span>At least one special character (!@#$...)</span>
                </li>
            </ul>
        </div>

        <div class="card-body flex flex-col gap-2 justify-center">

            <p id="pwResetError" class="text-center mb-3 text-red-600">
                <?php if (!empty($error)) {
                    echo htmlspecialchars($error);
                } ?>
            </p>



            <form method="POST" id="resetForm" action="includes/process_reset.php?token=<?php echo urlencode($token); ?>">
                <div class="form-control">
                    <label class="label flex items-center justify-start gap-2">
                        <i data-lucide="lock-keyhole" class="w-5 h-5 "></i>
                        <span class="label-text">New Password</span>
                    </label>
                    <input type="password" class="input input-bordered w-full" id="password" name="password" required />
                    <div class="flex flex-col items-center justify-start gap-2 ">
                        <div class="w-full bg-gray-200 rounded h-3 overflow-hidden mt-3">
                            <div id="strengthFill" class="h-3 bg-red-500 w-0 transition-all duration-500 ease-in-out"></div>
                        </div>
                        <p id="strengthText" class="text-center font-medium">Enter a password</p>
                    </div>

                </div>

                <div class="form-control mt-3">
                    <label class="label flex items-center justify-start gap-2">
                        <i data-lucide="shield-check" class="w-5 h-5 "></i>
                        <span class="label-text">Confirm Password</span>
                    </label>
                    <input type="password" class="input input-bordered w-full" id="confirm_password" name="confirm" required />
                </div>

                <div class="form-control mt-6">
                    <button type="submit" class="btn btn-primary w-full">
                        <i data-lucide="rotate-ccw-key" class="w-5 h-5"></i>
                        Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="js/reset-password.js"></script>
</body>

</html>