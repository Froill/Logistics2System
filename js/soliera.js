// Initialize lucide icons
lucide.createIcons();

const closeNav = "w-20";
const openNav = "w-96";

// Check if mobile view
function isMobileView() {
  return window.innerWidth < 768; // Tailwind's md breakpoint
}

// Toggle sidebar function
function toggleSidebar() {
  const sidebar = document.getElementById("sidebar");
  const sidebarLogo = document.getElementById("sidebar-logo");
  const sonlyLogo = document.getElementById("sonly");

  if (isMobileView()) {
    // Mobile behavior - toggle visibility
    if (sidebar.classList.contains("translate-x-0")) {
      // Closing sidebar on mobile
      sidebar.classList.remove("translate-x-0");
      sidebar.classList.add("-translate-x-full");
    } else {
      // Opening sidebar on mobile
      sidebar.classList.remove("-translate-x-full");
      sidebar.classList.add("translate-x-0");
    }
  } else {
    // Desktop behavior - toggle between expanded/collapsed
    const isCollapsed = sidebar.classList.toggle(openNav);
    sidebar.classList.toggle(closeNav, !isCollapsed);
    localStorage.setItem("sidebarCollapsed", !isCollapsed);

    // Update text visibility based on collapsed state
    document.querySelectorAll(".sidebar-text").forEach((text) => {
      text.classList.toggle("hidden", !isCollapsed);
    });

    // Toggle logos based on collapsed state
    if (sidebar.classList.contains(closeNav)) {
      sidebarLogo.classList.add("hidden");
      sonlyLogo.classList.remove("hidden");
    } else {
      sidebarLogo.classList.remove("hidden");
      sonlyLogo.classList.add("hidden");
    }
  }

  // Update dropdown indicators
  updateDropdownIndicators();
}

// Update dropdown indicators
function updateDropdownIndicators() {
  const sidebar = document.getElementById("sidebar");
  const isCollapsed = sidebar.classList.contains(closeNav) && !isMobileView();
  const dropdownIcons = document.querySelectorAll(".dropdown-icon");

  dropdownIcons.forEach((icon) => {
    if (isCollapsed) {
      const isOpen = icon
        .closest(".collapse")
        .querySelector('input[type="checkbox"]').checked;
      icon.setAttribute("data-lucide", isOpen ? "minus" : "plus");
    } else {
      const isOpen = icon
        .closest(".collapse")
        .querySelector('input[type="checkbox"]').checked;
      icon.setAttribute(
        "data-lucide",
        isOpen ? "chevron-down" : "chevron-right"
      );
    }
    lucide.createIcon(icon);
  });
}

// Handle window resize
function handleResize() {
  const sidebar = document.getElementById("sidebar");
  const sidebarLogo = document.getElementById("sidebar-logo");
  const sonlyLogo = document.getElementById("sonly");

  if (isMobileView()) {
    // On mobile, ensure proper transform classes and show full logo
    if (!sidebar.classList.contains("translate-x-0")) {
      sidebar.classList.add("-translate-x-full");
      sidebar.classList.remove("translate-x-0");
    }
    sidebarLogo.classList.remove("hidden");
    sonlyLogo.classList.add("hidden");
  } else {
    // On desktop, apply the saved collapsed state
    const isCollapsed = localStorage.getItem("sidebarCollapsed") === "true";
    sidebar.classList.remove("-translate-x-full", "translate-x-0");
    sidebar.classList.toggle(openNav, !isCollapsed);
    sidebar.classList.toggle(closeNav, isCollapsed);

    document.querySelectorAll(".sidebar-text").forEach((text) => {
      text.classList.toggle("hidden", isCollapsed);
    });

    // Toggle logos based on collapsed state
    if (isCollapsed) {
      sidebarLogo.classList.add("hidden");
      sonlyLogo.classList.remove("hidden");
    } else {
      sidebarLogo.classList.remove("hidden");
      sonlyLogo.classList.add("hidden");
    }
  }

  updateDropdownIndicators();
}

// Initialize sidebar
function initSidebar() {
  const sidebar = document.getElementById("sidebar");
  const sidebarLogo = document.getElementById("sidebar-logo");
  const sonlyLogo = document.getElementById("sonly");

  if (isMobileView()) {
    // Start hidden on mobile with full logo
    sidebar.classList.add("-translate-x-full");
    sidebarLogo.classList.remove("hidden");
    sonlyLogo.classList.add("hidden");
  } else {
    // Start with saved state on desktop
    const isCollapsed = localStorage.getItem("sidebarCollapsed") === "true";
    sidebar.classList.add(isCollapsed ? closeNav : openNav);

    document.querySelectorAll(".sidebar-text").forEach((text) => {
      text.classList.toggle("hidden", isCollapsed);
    });

    // Toggle logos based on collapsed state
    if (isCollapsed) {
      sidebarLogo.classList.add("hidden");
      sonlyLogo.classList.remove("hidden");
    } else {
      sidebarLogo.classList.remove("hidden");
      sonlyLogo.classList.add("hidden");
    }
  }

  setTimeout(() => {
    sidebar.classList.add("loaded");
  }, 50);

  // Set up event listeners
  document
    .querySelectorAll('.collapse input[type="checkbox"]')
    .forEach((checkbox) => {
      checkbox.addEventListener("change", updateDropdownIndicators);
    });

  window.addEventListener("resize", handleResize);
  updateDropdownIndicators();
}

function displayPhilippineTime() {
  // Create a date object for Philippine time (UTC+8)
  const options = {
    timeZone: "Asia/Manila",
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
    hour12: true,
  };

  // Get the formatted date and time string
  const philippineDateTime = new Date().toLocaleString("en-PH", options);

  // Update the element with the current time
  const timeElement = document.getElementById("philippineTime");
  if (timeElement) {
    timeElement.textContent = philippineDateTime;
  }
}

// Initial call to display the time
displayPhilippineTime();

// Update the time every second
setInterval(displayPhilippineTime, 1000);

(() => {
  const savedTheme = localStorage.getItem("theme") || "light";
  document.documentElement.setAttribute("data-theme", savedTheme);
})();

// Add event listener to ensure the function runs after DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  displayPhilippineTime();
  initSidebar();

  const toggle = document.getElementById("themeToggle");
  const html = document.documentElement;
  const themeLabel = document.getElementById("themeLabel");
  const themeIcon =
    document.querySelector("#themeLabel").previousElementSibling;

  // Load saved theme or detect system preference
  const savedTheme = localStorage.getItem("theme");
  const prefersDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
  const theme = savedTheme || (prefersDark ? "dark" : "light");

  // Helper to update label + icon
  const updateThemeUI = (theme) => {
    if (theme === "dark") {
      themeLabel.textContent = "Dark Mode";
      themeIcon.setAttribute("data-lucide", "moon-star");
    } else {
      themeLabel.textContent = "Light Mode";
      themeIcon.setAttribute("data-lucide", "sun");
    }
    lucide.createIcons();
  };

  // Apply theme immediately
  html.setAttribute("data-theme", theme);
  updateThemeUI(theme);

  // Ensure toggle reflects current theme
  if (toggle) {
    toggle.checked = theme === "dark";

    toggle.addEventListener("change", function () {
      html.classList.add("theme-transition");

      const newTheme = this.checked ? "dark" : "light";
      html.setAttribute("data-theme", newTheme);
      localStorage.setItem("theme", newTheme);
      updateThemeUI(newTheme);

      setTimeout(() => {
        html.classList.remove("theme-transition");
      }, 300);
    });
  }
});
