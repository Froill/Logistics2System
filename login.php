<?php
session_start();

// Redirect to home if already logged in
if (isset($_SESSION['user_id'])) {
  header("Location: dashboard.php");
  exit();
}

// Retain input and show error message
$eid = $_SESSION['eid'] ?? ''; // Retain eid from session if set
$error = $_SESSION['error'] ?? ''; // Show any login errors
$success = $_SESSION['success'] ?? ''; // Show reset success message    
unset($_SESSION['error']); // Clear error after display
unset($_SESSION['success']); // Clear reset success after display
unset($_SESSION['username']); // Clear username after using it
?>


<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <link rel="stylesheet" href="./css/style.css">
  <link rel="icon" type="image/x-icon" href="images/logo/sonly-2.png">
  <title>Login - Logistics 2</title>

  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css" />
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@5/themes.css" rel="stylesheet" type="text/css" />
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">


</head>

<body>
  <section class="relative w-full h-screen">

    <!-- Background image with overlay -->
    <div class="absolute inset-0 bg-cover bg-center z-0" style="background-image: url('./images/hotel3.jpg');"></div>
    <div class="absolute inset-0 bg-black/40 z-10"></div>
    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-black/70 z-10"></div>

    <!-- Content container -->
    <div class="relative z-10 w-full h-full flex justify-center items-center  p-4">
      <div class="w-1/2 flex justify-center items-center max-md:hidden">
        <div class="max-w-lg p-8">
          <!-- Hotel & Restaurant Illustration -->
          <div class="text-center mb-8">
            <a href="/">
              <img data-aos="zoom-in" data-aos-delay="100" class="w-full max-h-52 hover:scale-105 transition-all" src="logofinal.png" alt="">
            </a>
            <h1 data-aos="zoom-in-up" data-aos-delay="200" class="text-3xl font-bold text-white mb-2">Welcome to <span class="text-[#F7B32B]">Soliera<span> Hotel & Restaurant</h1>
            <p data-aos="zoom-in-up" data-aos-delay="300" class="text-white/80"> Savor The Stay, Dine With Elegance</p>
          </div>

          <!-- Features List -->
          <div data-aos="zoom-in-up" data-aos-delay="400" class="space-y-4">
            <div class="flex items-start">
              <div class="flex-shrink-0 mt-1">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-concierge-bell text-amber-400">
                  <path d="M2 18a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v2H2v-2Z" />
                  <path d="M20 16a8 8 0 1 0-16 0" />
                  <path d="M12 4v4" />
                  <path d="M10 4h4" />
                </svg>
              </div>
              <div class="ml-3">
                <p class="text-white font-medium">24/7 Concierge Service</p>
                <p class="text-white/70 text-sm">Personalized assistance whenever you need</p>
              </div>
            </div>

            <div class="flex items-start">
              <div class="flex-shrink-0 mt-1">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar-check text-amber-400">
                  <rect width="18" height="18" x="3" y="4" rx="2" ry="2" />
                  <line x1="16" x2="16" y1="2" y2="6" />
                  <line x1="8" x2="8" y1="2" y2="6" />
                  <line x1="3" x2="21" y1="10" y2="10" />
                  <path d="m9 16 2 2 4-4" />
                </svg>
              </div>
              <div class="ml-3">
                <p class="text-white font-medium">Easy Reservations</p>
                <p class="text-white/70 text-sm">Book rooms, tables, and services seamlessly</p>
              </div>
            </div>

            <div class="flex items-start">
              <div class="flex-shrink-0 mt-1">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-star text-amber-400">
                  <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                </svg>
              </div>
              <div class="ml-3">
                <p class="text-white font-medium">Exclusive Rewards</p>
                <p class="text-white/70 text-sm">Earn points with every stay and dining experience</p>
              </div>
            </div>
          </div>

        </div>
      </div>

      <div class="w-1/2 flex justify-center items-center max-md:w-full">
        <div class="max-w-md w-full bg-white/10 backdrop-blur-lg p-6 rounded-xl shadow-2xl border border-white/20">
          <!-- Card Header -->
          <div class="mb-6 text-center flex justify-center items-center flex-col">

            <h2 class="text-2xl font-bold text-white">Sign in to your account</h2>
            <p class="text-white/80 mt-1">Enter your credentials to continue</p>
          </div>

          <?php if (!empty($error)):  ?>

            <div class="alert alert-error flex flex-row alert-soft mb-5">
              <i data-lucide="octagon-alert"></i>
              <span><?php echo htmlspecialchars($error); ?></span>
            </div>
          <?php endif; ?>

          <?php if (!empty($success)):  ?>
            <div class="alert alert-success alert-soft flex flex-row mb-5">
              <i data-lucide="shield-check"></i>
              <span><?php echo htmlspecialchars($success); ?></span>
            </div>
          <?php endif; ?>

          <!-- Card Body -->
          <div>
            <form action="includes/validate_login.php" method="POST">
              <!-- Email Input -->

              <div class="mb-4">
                <label class="block text-white/90 text-sm font-medium mb-2" for="email">
                  Email
                </label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-white/50" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                      <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                    </svg>
                  </div>
                  <input
                    id="email"
                    type="text"
                    class="w-full pl-10 pr-3 py-3 bg-white/5 border border-white/20 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-transparent placeholder-white/50"
                    placeholder="Your Email"
                    required
                    name="eid">
                </div>
              </div>

              <!-- Password Input with Toggle -->
              <div class="mb-6">
                <label class="block text-white/90 text-sm font-medium mb-2" for="password">
                  Password
                </label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                  </div>
                  <input
                    id="password"
                    type="password"
                    class="w-full pl-10 pr-10 py-3 bg-white/5 border border-white/20 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-transparent placeholder-white/50"
                    placeholder="••••••••"
                    required
                    name="password">
                  <button
                    type="button"
                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-white/50 hover:text-white focus:outline-none"
                    onclick="togglePasswordVisibility()">
                    <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                      <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                      <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                    </svg>
                    <svg id="eye-slash-icon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd" />
                      <path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z" />
                    </svg>
                  </button>
                </div>
              </div>

              <!-- Forgot Password -->
              <div class="flex items-center justify-end mb-6">
                <div class="text-sm">
                  <button onclick="reset_password.showModal()" href="#"
                    class="font-medium text-blue-400 hover:text-blue-300 hover:underline cursor-pointer">
                    Forgot password?
                  </button>
                </div>
              </div>

              <!-- Sign In Button -->
              <button
                type="submit"
                class="w-full btn-primary btn">
                Sign in
              </button>


              <!-- reCAPTCHA v2 widget -->
              <div class="flex justify-center scale-75 sm:scale-100 mt-6">
                <div class="g-recaptcha" data-sitekey="6Lf6lrArAAAAAHAIJbwW50D8q4on5jwB-2MRw_Ho"></div>
              </div>
              <script src="https://www.google.com/recaptcha/api.js" async defer></script>
            </form>
          </div>
        </div>
      </div>



    </div>



  </section>

  <dialog id="reset_password" class="modal">
    <div class="modal-box">
      <form method="dialog">
        <button onclick="reset_password.close()" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
      </form>

      <h3 class="text-lg font-bold mb-4 text-center">Reset Your Password</h3>

      <form method="POST" action="includes/process_forgot.php" class="form flex flex-col items-center gap-3">
        <div class="form-control w-full">
          <label class="label flex items-center justify-start gap-2 mb-2">
            <i data-lucide="mail"></i>
            <span class="label-text">Email</span>
          </label>
          <input type="email" name="email" placeholder="Enter your email" class="input input-bordered w-full" required />
        </div>

        <div class="flex justify-center w-full mt-4">
          <button type="submit" class="btn btn-primary btn-outline submit-btn"> <i data-lucide="refresh-ccw" class="w-5 h-5"></i>Send Reset Link</button>
          <span class="loading loading-bars loading-md text-primary hidden"></span>
        </div>
      </form>
    </div>
  </dialog>

  <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>


  <script>
    AOS.init({
      duration: 1000,
      once: true
    });
  </script>




  <script>
    function togglePasswordVisibility() {
      const passwordInput = document.getElementById('password');
      const eyeIcon = document.getElementById('eye-icon');
      const eyeSlashIcon = document.getElementById('eye-slash-icon');

      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.classList.add('hidden');
        eyeSlashIcon.classList.remove('hidden');
      } else {
        passwordInput.type = 'password';
        eyeIcon.classList.remove('hidden');
        eyeSlashIcon.classList.add('hidden');
      }
    }
  </script>

  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="js/login.js"></script>


</body>

</html>