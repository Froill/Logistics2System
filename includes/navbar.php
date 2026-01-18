<header class="bg-base-100 shadow-sm z-10 border-b dark:border-gray-700 w-full">

  <!--<header class="shadow-sm z-10 border-b border-base-300 dark:border-gray-700 w-full">-->
  <div class="px-4 sm:px-6 lg:px-8">
    <div class="navbar h-16">
      <div class="navbar-start flex items-center">
        <button onclick="toggleSidebar()" class="btn btn-ghost btn-sm  transition-all hover:scale-105">
          <i data-lucide="menu" class="w-5 h-5"></i>
        </button>
      </div>
      <div class="navbar-end flex items-center gap-4">
        <!-- Time Display -->
        <div class="animate-fadeIn">
          <span id="philippineTime" class="font-medium max-sm:text-xs"></span>
        </div>

        <!-- Notification Dropdown -->
        <div class="dropdown dropdown-end">


          <!-- Button -->
          <button id="notification-button" tabindex="0" class="btn btn-ghost btn-circle btn-sm relative">
            <i data-lucide="bell" class="w-5 h-5 "></i>
            <!-- <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span> -->
          </button>

          <!-- Dropdown Content - Responsive -->
          <ul tabindex="0" class="dropdown-content bg-base-100 menu mt-3 z-[1] rounded-lg shadow-xl overflow-hidden transform md:translate-x-0 sm:translate-x-1/2 sm:-translate-x-1/2">
            <!-- Header -->
            <li class="px-4 py-3 border-b  flex justify-between items-center sticky top-0 backdrop-blur-sm z-10">
              <div class="flex items-center gap-2">
                <i data-lucide="bell" class="w-5 h-5 "></i>
                <span class="font-semibold">Notifications</span>
              </div>
              <button class="text-sm flex items-center gap-1">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
                <span>Clear All</span>
              </button>
            </li>

            <!-- Notification Items Container - Scrollable -->
            <div class="max-h-96 overflow-y-auto">
              <!-- Notification Items -->
              <!-- <li class="px-4 py-3 hover:scale-105 transition-all">
                <a class="bg-blue-700/50 flex items-start gap-3">
                  <div class="p-2 rounded-full bg-blue-600/30 ">
                    <i data-lucide="calendar-check" class="w-5 h-5"></i>
                  </div>
                  <div class="flex-1">
                    <p class="font-medium flex items-center gap-2">
                      New Reservation
                      <span class="text-xs px-1.5 py-0.5 bg-blue-600 rounded-full">New</span>
                    </p>
                    <p class="text-sm mt-1">John Doe booked Deluxe Suite for 3 nights</p>
                    <p class="text-xs mt-2 flex items-center gap-1">
                      <i data-lucide="clock" class="w-3 h-3"></i>
                      10 minutes ago
                    </p>
                  </div>
                </a>
              </li>

              <li class="px-4 py-3 hover:scale-105 transition-all">
                <a class="bg-blue-700/50  flex items-start gap-3">
                  <div class="p-2 rounded-full bg-green-600/30 text-green-300">
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                  </div>
                  <div class="flex-1">
                    <p class="font-medium">Check-in Complete</p>
                    <p class="text-sm mt-1">Room 302 has been checked in</p>
                    <p class="text-xs mt-2 flex items-center gap-1">
                      <i data-lucide="clock" class="w-3 h-3"></i>
                      1 hour ago
                    </p>
                  </div>
                </a>
              </li>

              <li class="px-4 py-3 hover:scale-105 transition-all">
                <a class="bg-red-600 flex items-start gap-3">
                  <div class="p-2 rounded-full bg-yellow-600/30 text-yellow-300">
                    <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                  </div>
                  <div class="flex-1">
                    <p class="font-medium flex items-center gap-2">
                      Maintenance Request
                      <span class="text-xs px-1.5 py-0.5 bg-yellow-600 rounded-full">Urgent</span>
                    </p>
                    <p class="text-sm mt-1">AC not working in Room 215</p>
                    <p class="text-xs mt-2 flex items-center gap-1">
                      <i data-lucide="clock" class="w-3 h-3"></i>
                      3 hours ago
                    </p>
                  </div>
                </a>
              </li>

              <li class="px-4 py-3 hover:scale-105 transition-all">
                <a class="bg-blue-700/50  flex items-start gap-3">
                  <div class="p-2 rounded-full bg-purple-600/30 text-purple-300">
                    <i data-lucide="message-circle" class="w-5 h-5"></i>
                  </div>
                  <div class="flex-1">
                    <p class="font-medium">Guest Message</p>
                    <p class="text-sm mt-1">Request for late checkout</p>
                    <p class="text-xs mt-2 flex items-center gap-1">
                      <i data-lucide="clock" class="w-3 h-3"></i>
                      5 hours ago
                    </p>
                  </div>
                </a>
              </li>

              <li class="px-4 py-3 hover:scale-105 transition-all">
                <a class="bg-red-600 flex items-start gap-3">
                  <div class="p-2 rounded-full bg-red-600/30 text-red-300">
                    <i data-lucide="alert-octagon" class="w-5 h-5"></i>
                  </div>
                  <div class="flex-1">
                    <p class="font-medium">Security Alert</p>
                    <p class="text-sm mt-1">Unauthorized access attempt</p>
                    <p class="text-xs mt-2 flex items-center gap-1">
                      <i data-lucide="clock" class="w-3 h-3"></i>
                      1 day ago
                    </p>
                  </div>
                </a>
              </li>

              <li class="px-4 py-3 hover:scale-105 transition-all">
                <a class="bg-blue-700/50  flex items-start gap-3">
                  <div class="p-2 rounded-full bg-blue-600/30 ">
                    <i data-lucide="credit-card" class="w-5 h-5"></i>
                  </div>
                  <div class="flex-1">
                    <p class="font-medium">Payment Received</p>
                    <p class="text-sm mt-1">$450 for Room 204</p>
                    <p class="text-xs mt-2 flex items-center gap-1">
                      <i data-lucide="clock" class="w-3 h-3"></i>
                      2 days ago
                    </p>
                  </div>
                </a>
              </li> 
              -->
            </div>

            <!-- Footer -->
            <li class="px-4 py-2 border-t  sticky bottom-0">
              <a class="text-center   text-sm flex items-center justify-center gap-1">
                <i data-lucide="list" class="w-4 h-4"></i>
                <span>View All Notifications</span>
              </a>
            </li>
          </ul>
        </div>


        <!-- User Dropdown -->
        <div class="dropdown dropdown-end">
          <label tabindex="0" class="btn btn-ghost btn-circle">
            <div class="w-10 h-10 flex items-center justify-center rounded-full bg-gradient-to-tr from-blue-600 via-blue-500 to-blue-400 font-bold">
              <span class="text-center text-xl text-white"><?= strtoupper(substr($full_name, 0, 1)) ?></span>
            </div>
          </label>
          <ul tabindex="0" class="dropdown-content bg-base-100 menu mt-1 z-40 w-52 rounded-box shadow-xl">
            <!-- User Profile Section -->
            <li class="p-3 border-b ">
              <div class=" rounded-md shadow-md flex items-center gap-3">
                <div class="w-10 h-10 flex items-center justify-center rounded-full bg-gradient-to-tr from-blue-600 via-blue-500 to-blue-400 font-bold">
                  <span class="text-center text-xl text-white"><?= strtoupper(substr($full_name, 0, 1)) ?></span>
                </div>
                <div>
                  <p class="font-medium"><?= htmlspecialchars($full_name) ?></p>
                  <p class="text-xs"><?= htmlspecialchars($eid) ?></p>
                  <p class="text-xs"><?= htmlspecialchars(ucfirst($role)) ?></p>
                </div>
              </div>
            </li>

            <!-- Menu Items -->
            <li>
              <a href="./dashboard.php?module=profile" class="flex items-center gap-2 px-4 py-2">
                <i data-lucide="user" class="w-4 h-4"></i>
                <span>Profile</span>
              </a>
            </li>
            <li>
              <label class="flex items-center gap-2 px-4 py-2 cursor-pointer">
                <i data-lucide="moon-star" class="w-4 h-4"></i>
                <span id="themeLabel">Dark Mode</span>
                <input id="themeToggle" type="checkbox" class="toggle theme-controller" />
              </label>
            </li>
            <li class="">
              <a onclick="logoutModal.showModal()" class="flex items-center gap-2 px-4 py-2">
                <i data-lucide="log-out" class="w-4 h-4"></i>
                <span>Sign out</span>
              </a>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</header>


<style>
  @media (max-width: 767px) {
    .dropdown-content {
      left: 50% !important;
      transform: translateX(-80%) !important;


    }
  }

  /* Ensure navbar background stays white and icons render black */
  /* header {
    background-color: #ffffff !important;
  } */

  /* header i,
  header svg {
    color: #000000 !important;
    stroke: #000000 !important;
    fill: #000000 !important;
  } */

  /* Fix any utility class forcing white text inside the header */
  /* header .text-white {
    color: #000000 !important;
  } */
</style>