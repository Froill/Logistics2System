document.addEventListener("DOMContentLoaded", function () {
  lucide.createIcons();
  document.addEventListener("submit", function (event) {
    if (event.target.className.includes("form")) {
      const loginButton = event.target.querySelector('button[type="submit"]');
      const loadingIndicator = event.target.querySelector(".loading");
      loginButton.classList.add("hidden");
      loadingIndicator.classList.remove("hidden");
    }
  });
});
