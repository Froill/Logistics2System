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

  //   Password strength meter
  const passwordInput = document.getElementById("password");
  const strengthBar = document.getElementById("strengthBar");
  const strengthText = document.getElementById("strengthText");

  const items = {
    length: document.getElementById("tip-length"),
    uppercase: document.getElementById("tip-uppercase"),
    lowercase: document.getElementById("tip-lowercase"),
    number: document.getElementById("tip-number"),
    symbol: document.getElementById("tip-symbol"),
  };

  const setTipState = (li, passed) => {
    const xIcon = li.querySelector(".icon-x");
    const okIcon = li.querySelector(".icon-check");
    if (passed) {
      xIcon.classList.add("hidden");
      okIcon.classList.remove("hidden");
      li.classList.add("text-green-400");
      li.classList.remove("text-red-300");
    } else {
      okIcon.classList.add("hidden");
      xIcon.classList.remove("hidden");
      li.classList.add("text-red-300");
      li.classList.remove("text-green-400");
    }
  };

  passwordInput.addEventListener("input", () => {
    const v = passwordInput.value;
    const checks = {
      length: v.length >= 8,
      uppercase: /[A-Z]/.test(v),
      lowercase: /[a-z]/.test(v),
      number: /[0-9]/.test(v),
      symbol: /[^A-Za-z0-9]/.test(v),
    };

    let score = 0;
    for (const [k, passed] of Object.entries(checks)) {
      setTipState(items[k], passed);
      if (passed) score += 20;
    }

    strengthBar.value = score;
    if (score < 40) {
      strengthBar.className = "progress progress-error w-full mt-3";
      strengthText.textContent = "WEAK";
      strengthText.className = "text-center font-medium text-red-900";
    } else if (score < 80) {
      strengthBar.className = "progress progress-warning w-full mt-3";
      strengthText.textContent = "MEDIUM";
      strengthText.className = "text-center font-medium text-yellow-900";
    } else {
      strengthBar.className = "progress progress-success w-full mt-3";
      strengthText.textContent = "STRONG";
      strengthText.className = "text-center font-medium text-green-900";
    }
  });
});
