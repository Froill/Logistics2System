<div class="bg-[#001f54] pt-5 pb-4 flex flex-col fixed md:relative h-full transition-all duration-300 ease-in-out shadow-xl transform -translate-x-full md:transform-none md:translate-x-0" id="sidebar">
  <!-- Sidebar Header -->
  <div class="flex items-center justify-between flex-shrink-0 px-4 mb-6 text-center">
    <h1 class="text-xl font-bold text-white items-center gap-2 px-1 justify-center flex">
      <img id="sidebar-logo" class="hidden" src="images/logo/logofinal.png" alt="">
      <img id="sonly" class="hidden w-auto h-12" src="images/logo/sonly-2.png" alt="">

    </h1>
  </div>



  <!-- Navigation Menu -->
  <div class="flex-1 flex flex-col overflow-y-auto">
    <nav class="flex-1 px-2 space-y-1">
      <!-- Section Label -->
      <div class="px-4 py-2">
        <span class="text-xs font-semibold uppercase tracking-wider text-blue-300 sidebar-text">Main Menu</span>
      </div>

      <!-- Dashboard -->
      <a href="" class="block">
        <div class=" flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
          <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
            <i data-lucide="home" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
          </div>
          <span class="ml-3 sidebar-text">Dashboard</span>
        </div>
      </a>

      <!-- Front Desk -->
      <a href="/frontdesk" class="block">
        <div class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
          <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
            <i data-lucide="monitor" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
          </div>
          <span class="ml-3 sidebar-text">Front Desk</span>
        </div>
      </a>

      <!-- Section Label -->
      <div class="px-4 py-2 mt-4">
        <span class="text-xs font-semibold uppercase tracking-wider text-blue-300 sidebar-text">Operations</span>
      </div>

      <!-- Reservation And Booking -->
      <div class="collapse group">
        <input type="checkbox" class="peer" />
        <div class="collapse-title flex items-center justify-between px-4 py-3 text-sm font-medium rounded-lg transition-all peer-checked:bg-blue-600/50 text-white group">
          <div class="flex items-center">
            <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
              <i data-lucide="calendar-check" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
            </div>
            <span class="ml-3 sidebar-text">Reservations</span>
          </div>
          <i class="w-4 h-4 text-blue-200 transform transition-transform duration-200 peer-checked:rotate-90 dropdown-icon" data-lucide="chevron-down"></i>
        </div>
        <div class="collapse-content pl-14 pr-4 py-1 space-y-1">
          <a href="/bas" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
            <span class="flex items-center gap-2">
              <i data-lucide="bed" class="w-4 h-4 text-[#F7B32B]"></i>
              Room Reservation
            </span>
          </a>
          <a href="/aibas" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
            <span class="flex items-center gap-2">
              <i data-lucide="bot" class="w-4 h-4 text-[#F7B32B]"></i>
              AI Assistance
            </span>
          </a>
        </div>
      </div>

      <!-- Guest Management -->
      <div class="collapse group">
        <input type="checkbox" class="peer" />
        <div class="collapse-title flex items-center justify-between px-4 py-3 text-sm font-medium rounded-lg transition-all peer-checked:bg-blue-600/50 text-white group">
          <div class="flex items-center">
            <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
              <i data-lucide="users" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
            </div>
            <span class="ml-3 sidebar-text ">Guest Management</span>
          </div>
          <i class="w-4 h-4 text-blue-200 transform transition-transform duration-200 peer-checked:rotate-90 dropdown-icon" data-lucide="chevron-down"></i>
        </div>
        <div class="collapse-content pl-14 pr-4 py-1 space-y-1">
          <a href="#" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
            <span class="flex items-center gap-2">
              <i data-lucide="star" class="w-4 h-4 text-[#F7B32B]"></i>
              Room Feedbacks
            </span>
          </a>
          <a href="#" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
            <span class="flex items-center gap-2">
              <i data-lucide="heart" class="w-4 h-4 text-[#F7B32B]"></i>
              Service Feedbacks
            </span>
          </a>
        </div>
      </div>

      <!-- Room Management -->
      <div class="collapse group">
        <input type="checkbox" class="peer" />
        <div class="collapse-title flex items-center justify-between px-4 py-3 text-sm font-medium rounded-lg transition-all peer-checked:bg-blue-600/50 text-white group">
          <div class="flex items-center">
            <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
              <i data-lucide="door-open" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
            </div>
            <span class="ml-3 sidebar-text">Room Management</span>
          </div>
          <i class="w-4 h-4 text-blue-200 transform transition-transform duration-200 peer-checked:rotate-90 dropdown-icon" data-lucide="chevron-down"></i>
        </div>
        <div class="collapse-content pl-14 pr-4 py-1 space-y-1">
          <a href="/roommanagement" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
            <span class="flex items-center gap-2">
              <i data-lucide="bed" class="w-4 h-4 text-[#F7B32B]"></i>
              Rooms
            </span>
          </a>
          <a href="/servicemanagement" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
            <span class="flex items-center gap-2">
              <i data-lucide="key" class="w-4 h-4 text-[#F7B32B]"></i>
              Door Locks
            </span>
          </a>
        </div>
      </div>

      <!-- Section Label -->
      <div class="px-4 py-2 mt-4">
        <span class="text-xs font-semibold uppercase tracking-wider text-blue-300 sidebar-text">Business</span>
      </div>

      <!-- Other menu items -->
      <a href="" class="block">
        <div class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
          <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
            <i data-lucide="shopping-cart" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
          </div>
          <span class="ml-3 sidebar-text">Point of Sale</span>
        </div>
      </a>

      <a href="/lar" class="block">
        <div class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
          <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
            <i data-lucide="award" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
          </div>
          <span class="ml-3 sidebar-text">Loyalty And Rewards</span>
        </div>
      </a>


      <a href="/ias" class="block">
        <div class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
          <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
            <i data-lucide="package" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
          </div>
          <span class="ml-3 sidebar-text">Inventory</span>
        </div>
      </a>

      <a href="/ecm" class="block">
        <div class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
          <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
            <i data-lucide="calendar-days" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
          </div>
          <span class="ml-3 sidebar-text">Events</span>
        </div>
      </a>

      <a href="/hmp" class="block">
        <div class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
          <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
            <i data-lucide="megaphone" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
          </div>
          <span class="ml-3 sidebar-text">Marketing</span>
        </div>
      </a>

      <a href="/hmm" class="block">
        <div class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
          <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
            <i data-lucide="brush-cleaning" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
          </div>
          <span class="ml-3 sidebar-text">Housekeeping</span>
        </div>
      </a>

      <a href="/channel" class="block">
        <div class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
          <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
            <i data-lucide="share-2" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
          </div>
          <span class="ml-3 sidebar-text">Channels</span>
        </div>
      </a>
    </nav>
  </div>
</div>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>


<style>
  /* Mobile styles */
  @media (max-width: 767px) {
    #sidebar {
      z-index: 40;
      width: 16rem;
      /* w-64 equivalent */
      left: 0;
      top: 0;
      bottom: 0;
      transition: transform 0.3s ease;
    }

    #sidebar.translate-x-0 {
      transform: translateX(0);
    }

    #sidebar.-translate-x-full {
      transform: translateX(-100%);
    }

    /* Optional overlay */
    .sidebar-overlay {
      position: fixed;
      top: 0;
      right: 0;
      bottom: 0;
      left: 0;
      background: rgba(0, 0, 0, 0.5);
      z-index: 30;
      display: none;
    }

    #sidebar.translate-x-0+.sidebar-overlay {
      display: block;
    }
  }

  /* Desktop styles */
  @media (max-width: 1024px) {

    .w-20 .sidebar-text,
    .w-20 .lucide-chevron-down {
      display: none;
    }

    .w-20 .flex.items-center {
      justify-content: center;
      padding-left: 0.5rem;
      padding-right: 0.5rem;
    }

    .w-20 .collapse-title {
      padding-left: 0.5rem;
      padding-right: 0.5rem;
      justify-content: center;
    }

    .w-20 .collapse-content {
      display: none;
    }

    .w-20 .text-xs.uppercase {
      display: none;
    }

    .w-20 .p-1.5.rounded-lg {
      margin-right: 0;
    }

    #sidebar-logo {
      opacity: 0;
      transition: opacity 0.3s ease;
    }

    #sidebar.loaded #sidebar-logo {
      opacity: 1;
    }
  }
</style>