<?php
session_start();

$success = $_SESSION['otp_success'] ?? '';
unset($_SESSION['otp_success']);

$error = $_SESSION['otp_error'] ?? '';
unset($_SESSION['otp_error']);
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
        <div class="bg-[#001f54] text-white py-8 px-6 text-center w-full rounded-t-md border-b-2 border-solid border-yellow-700 flex flex-col items-center">
            <h1 class="card-title justify-center mb-1">Verify OTP</h1>
            <p class="mb-4">Please enter the 6-digit code sent to your email</p>

            <span class="text-sm flex text-gray-300 items-center gap-3">
                <i data-lucide="circle-alert" class="size-10"></i>

                <p class="text-left">You’ll only need this on new devices. Verified devices are trusted for 7 days. </p>
            </span>
        </div>
        <div class="card-body flex flex-col gap-2 justify-center">

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
                <div class="text-center mt-2 text-sm">
                    <p class="text-sm mb-2 text-gray-600"> </p>
                    <span>Didn’t receive the code?</span>
                    <form method="POST" action="includes/resend-otp.php" class="inline">
                        <button type="submit" class="text-blue-900 hover:underline ml-1">Resend OTP</button>
                    </form>
                </div>
            </div>
        </div>
        <script src=" https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
        <script>
            lucide.createIcons();
        </script>
</body>

</html>