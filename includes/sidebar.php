<div class="bg-[#001f54] pt-5 pb-4 flex flex-col fixed md:relative transition-all duration-300 ease-in-out 
shadow-xl transform -translate-x-full md:transform-none md:translate-x-0 z-40 h-screen md:h-auto" id="sidebar">
  <!-- Sidebar Header -->
  <div class="flex flex-col items-center justify-between flex-shrink-0 px-4 mb-6 text-center">
    <button onclick="toggleSidebar()" class="btn text-white btn-ghost btn-sm self-end block md:hidden">
      <i data-lucide="x" class="w-5 h-5"></i>
    </button>
    <h1 class="text-xl font-bold text-white items-center gap-2 px-1 justify-center flex">
      <img id="sidebar-logo" class="hidden" src="images/logo/logofinal.png" alt="">
      <img id="sonly" class="hidden w-auto h-12" src="images/logo/sonly-2.png" alt="">

    </h1>
  </div>



  <!-- Navigation Menu -->
  <div class="flex-1 flex flex-col overflow-y-auto overscroll-contain">
    <nav class="flex-1 px-2 space-y-1">
      <!-- Section Label -->
      <div class="px-4 py-2">
        <span class="text-xs font-semibold uppercase tracking-wider text-blue-300 sidebar-text">Main Menu</span>
      </div>

      <!-- Dashboard -->
      <a href="dashboard.php" class="block">
        <div class=" flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
          <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
            <i data-lucide="home" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
          </div>
          <span class="ml-3 sidebar-text">Dashboard</span>
        </div>
      </a>



      <!-- Section Label -->
      <div class="px-4 py-2 mt-4">
        <span class="text-xs font-semibold uppercase tracking-wider text-blue-300 sidebar-text">Operations</span>
      </div>

      <!-- Fleet & Vehicle Management -->
      <!-- <div class="collapse group">
        <input type="checkbox" class="peer" />
        <div class="collapse-title flex items-center justify-between px-4 py-3 text-sm font-medium rounded-lg transition-all peer-checked:bg-blue-600/50 text-white group">
          <div class="flex items-center">
            <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
              <i data-lucide="car-front" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
            </div>
            <span class="ml-3 sidebar-text">Fleet & Vehicle Management</span>
          </div>
          <i class="w-4 h-4 text-blue-200 transform transition-transform duration-200 peer-checked:rotate-90 dropdown-icon" data-lucide="chevron-down"></i>
        </div>
        <div class="collapse-content pl-14 pr-4 py-1 space-y-1">
          <a href="#" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
            <span class="flex items-center gap-2">
              <i data-lucide="square" class="w-4 h-4 text-[#F7B32B]"></i>
              Label here
            </span>
          </a>
          <a href="/aibas" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
            <span class="flex items-center gap-2">
              <i data-lucide="square" class="w-4 h-4 text-[#F7B32B]"></i>
              Label here
            </span>

          </a>
        </div>
      </div> -->
      <a href="./dashboard.php?module=fvm" class="block">
        <div class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
          <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
            <i data-lucide="car-front" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
          </div>
          <span class="ml-3 sidebar-text">Fleet & Vehicle Management</span>
        </div>
      </a>
      <!-- Vehicle Routing & Dispatch -->
      <!-- <div class="collapse group">
        <input type="checkbox" class="peer" />
        <div class="collapse-title flex items-center justify-between px-4 py-3 text-sm font-medium rounded-lg transition-all peer-checked:bg-blue-600/50 text-white group">
          <div class="flex items-center">
            <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
              <i data-lucide="calendar-clock" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
            </div>
            <span class="ml-3 sidebar-text ">Vehicle Routing & Dispatch</span>
          </div>
          <i class="w-4 h-4 text-blue-200 transform transition-transform duration-200 peer-checked:rotate-90 dropdown-icon" data-lucide="chevron-down"></i>
        </div>
        <div class="collapse-content pl-14 pr-4 py-1 space-y-1">
          <a href="#" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
            <span class="flex items-center gap-2">
              <i data-lucide="square" class="w-4 h-4 text-[#F7B32B]"></i>
              Label here
            </span>
          </a>
          <a href="#" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
            <span class="flex items-center gap-2">
              <i data-lucide="square" class="w-4 h-4 text-[#F7B32B]"></i>
              Label here
            </span>
          </a>
        </div>
      </div> -->
      <a href="./dashboard.php?module=vrds" class="block">
        <div class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
          <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
            <i data-lucide="calendar-clock" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
          </div>
          <span class="ml-3 sidebar-text">Vehicle Reservation & Dispatch</span>
        </div>
      </a>

      <!-- Driver & Trip Performance -->
      <!-- <div class="collapse group">
        <input type="checkbox" class="peer" />
        <div class="collapse-title flex items-center justify-between px-4 py-3 text-sm font-medium rounded-lg transition-all peer-checked:bg-blue-600/50 text-white group">
          <div class="flex items-center">
            <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
              <i data-lucide="gauge" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
            </div>
            <span class="ml-3 sidebar-text">Driver & Trip Performance
            </span>
          </div>
          <i class="w-4 h-4 text-blue-200 transform transition-transform duration-200 peer-checked:rotate-90 dropdown-icon" data-lucide="chevron-down"></i>
        </div>
        <div class="collapse-content pl-14 pr-4 py-1 space-y-1">
          <a href="/roommanagement" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
            <span class="flex items-center gap-2">
              <i data-lucide="square" class="w-4 h-4 text-[#F7B32B]"></i>
              Label here
            </span>
          </a>
          <a href="/servicemanagement" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
            <span class="flex items-center gap-2">
              <i data-lucide="square" class="w-4 h-4 text-[#F7B32B]"></i>
              Label here
            </span>
          </a>
        </div>
      </div> -->
      <a href="./dashboard.php?module=driver_trip" class="block">
        <div class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
          <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
            <i data-lucide="gauge" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
          </div>
          <span class="ml-3 sidebar-text">Driver & Trip Performance</span>
        </div>
      </a>

      <!-- Transport Cost Analysis & Optimization -->
      <!-- <div class="collapse group">
        <input type="checkbox" class="peer" />
        <div class="collapse-title flex items-center justify-between px-4 py-3 text-sm font-medium rounded-lg transition-all peer-checked:bg-blue-600/50 text-white group">
          <div class="flex items-center">
            <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
              <i data-lucide="chart-line" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
            </div>
            <span class="ml-3 sidebar-text">Transport Cost Analysis & Optimization
            </span>
          </div>
          <i class="w-4 h-4 text-blue-200 transform transition-transform duration-200 peer-checked:rotate-90 dropdown-icon" data-lucide="chevron-down"></i>
        </div>
        <div class="collapse-content pl-14 pr-4 py-1 space-y-1">
          <a href="/roommanagement" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
            <span class="flex items-center gap-2">
              <i data-lucide="square" class="w-4 h-4 text-[#F7B32B]"></i>
              Label here
            </span>
          </a>
          <a href="/servicemanagement" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
            <span class="flex items-center gap-2">
              <i data-lucide="square" class="w-4 h-4 text-[#F7B32B]"></i>
              Label here
            </span>
          </a>
        </div>
      </div> -->
      <a href="./dashboard.php?module=tcao" class="block">
        <div class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
          <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
            <i data-lucide="chart-line" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
          </div>
          <span class="ml-3 sidebar-text">Transport Cost Analysis & Optimization</span>
        </div>
      </a>

      <?php
      if ($_SESSION['role'] === 'admin') {
        echo '
        <!-- Other menu items -->
        <a href="./dashboard.php?module=user_management" class="block">
          <div class="flex items"> </div>

      <!-- Section Label -->
      <div class="px-4 py-2 mt-4">
        <span class="text-xs font-semibold uppercase tracking-wider text-blue-300 sidebar-text">Admin</span>
      </div>

      <!-- Other menu items -->
      <a href="./dashboard.php?module=user_management" class="block">
        <div class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
          <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
            <i data-lucide="users" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
          </div>
          <span class="ml-3 sidebar-text">User Management</span>
        </div>
      </a>

  <a href="./dashboard.php?module=audit_log" class="block">
        <div class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
          <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
            <i data-lucide="square-activity" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
          </div>
          <span class="ml-3 sidebar-text">Audit Logs</span>
        </div>
      </a>';
      }
      ?>
    </nav>
  </div>
</div>
<div class="sidebar-overlay z-30" onclick="toggleSidebar()"></div>