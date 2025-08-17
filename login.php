<?php session_start(); ?>
<!DOCTYPE html>
<html data-theme="light" lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logistics 2 - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.4.19/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="card w-96 bg-base-100 shadow-xl">
        <div class="card-body">
            <h2 class="card-title justify-center">Logistics 2 Login</h2>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error shadow-lg">
                    <span><?= $_SESSION['error'];
                            unset($_SESSION['error']); ?></span>
                </div>
            <?php endif; ?>

            <form action="validate_login.php" method="POST">
                <div class="form-control">
                    <label class="label">Username</label>
                    <input type="text" name="username" class="input input-bordered" required>
                </div>

                <div class="form-control mt-2">
                    <label class="label">Password</label>
                    <input type="password" name="password" class="input input-bordered" required>
                </div>

                <div class="form-control mt-4">
                    <button type="submit" class="btn btn-primary">Login</button>
                </div>
            </form>
        </div>
    </div>

</body>

</html>