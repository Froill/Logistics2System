<?php
session_start();

if (
    !isset($_SESSION['otp']) ||
    !isset($_SESSION['pending_user']) ||
    (isset($_SESSION['otp_attempts']) && $_SESSION['otp_attempts'] >= 3)
) {
    session_destroy();
    header("Location: login.php?failed-attempt=1");
    exit();
}

// Check if OTP has expired
if (time() > $_SESSION['otp_expires']) {
    session_destroy();
    header("Location: login.php?failed-attempt=1");
    exit();
}

$success = $_SESSION['otp_success'] ?? '';
unset($_SESSION['otp_success']);

$error = $_SESSION['otp_error'] ?? '';
unset($_SESSION['otp_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enteredOtp = trim($_POST['otp']);

    if (empty($enteredOtp)) {
        $_SESSION['otp_error'] = "OTP is required.";
        header("Location: verify-otp.php");
        exit();
    }

    // Check for OTP expiration
    if (time() > $_SESSION['otp_expires']) {
        $_SESSION['otp_error'] = "OTP expired. Please log in again.";
        session_destroy();
        header("Location: login.php?failed-attempt=1");
        exit();
    }

    // Verify OTP
    if ($enteredOtp === $_SESSION['otp']) {
        // OTP is correct → complete login
        $_SESSION['user_id'] = $_SESSION['pending_user']['id'];
        $_SESSION['user_role'] = $_SESSION['pending_user']['role'];
        $_SESSION['username'] = $_SESSION['pending_user']['username'];



        // Cleanup
        unset($_SESSION['otp']);
        unset($_SESSION['otp_expires']);
        unset($_SESSION['pending_user']);

        header("Location: student-fees.php");
        exit();
    } else {
        $_SESSION['otp_error'] = "Invalid OTP. Please try again.";
        header("Location: verify-otp.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html data-theme="light" lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - Logistics 2</title>
    <link rel="shortcut icon" href="images/logo/favicon.ico" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.4.19/dist/full.min.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="./css/style.css">


</head>

<body class=" flex items-center justify-center min-h-screen bg-gradient-to-r from-slate-700 to-slate-900">

    <div class="card w-96 shadow-xl bg-white">
        <div class="bg-[#001f54] text-white py-8 w-full rounded-t-md border-b-2 border-solid border-yellow-700 flex flex-col items-center">
            <h1 class="card-title justify-center mb-1">Verify OTP</h1>
            <p class="mb-4">Please enter the 6-digit code sent to your email</p>
            <p class="text-sm text-gray-300 flex items-center gap-2">
                <i data-lucide="circle-alert" class="text-sm"></i>
                Check your spam folder
            </p>
        </div>
        <div class="card-body flex flex-col gap-2 justify-center">

            <!-- <span class="text-center mb-3 text-red-600">Incorrect OTP</span> -->


            <?php if (!empty($success)): ?>
                <p class="text-center mb-3 text-green-600"><?php echo htmlspecialchars($success); ?></p>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <p class="text-center mb-3 text-red-600"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>

            <form method="POST" action="includes/validate_otp.php">
                <div class="form-control">
                    <input type="text" class="input input-bordered text-center w-full tracking-widest" name="otp" placeholder="" autofocus required />
                </div>
                <div class=" form-control mt-6">
                    <button type="submit" class="btn btn-primary"><i data-lucide="key-round" class="w-5 h-5"></i>Verify</button>
                </div>
            </form>
            <div class="text-center mt-2">
                <p class="text-sm">Didn't receive the code? </p>
                <form method="POST" action="includes/resend-otp.php">
                    <button type="submit" class="text-blue-900 hover:underline">Resend OTP</button>
                </form>

            </div>
        </div>
        <script src=" https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
        <script>
            lucide.createIcons();
        </script>
</body>

</html>