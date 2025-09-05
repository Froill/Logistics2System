document.addEventListener("DOMContentLoaded", function () {
  lucide.createIcons();

  const passwordInput = document.getElementById("password");
  const strengthFill = document.getElementById("strengthFill");
  const strengthText = document.getElementById("strengthText");
  const pwResetError = document.getElementById("pwResetError");

  document.addEventListener("submit", (event) => {
    if (event.target.id === "resetForm") {
      const password = document.getElementById("password").value;
      const confirmPassword = document.getElementById("confirm_password").value;

      if (password !== confirmPassword) {
        event.preventDefault();
        pwResetError.textContent = "Passwords do not match!";
        return;
      }

      if (calculateScore(password) < 100) {
        event.preventDefault();
        pwResetError.textContent = "Password is not strong enough!";
        return;
      }
    }
  });

  // Calculate score based on rules
  function calculateScore(v) {
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
    return score;
  }

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

  // Animate strength bar on input
  passwordInput.addEventListener("input", () => {
    const v = passwordInput.value;
    const score = calculateScore(v);

    // Animate width with Tailwind transitions
    strengthFill.style.width = score + "%";

    if (score < 40) {
      strengthFill.className =
        "h-3 bg-red-500 transition-all duration-500 ease-in-out";
      strengthText.textContent = "WEAK";
      strengthText.className = "text-center font-medium text-red-900";
    } else if (score < 80) {
      strengthFill.className =
        "h-3 bg-yellow-500 transition-all duration-500 ease-in-out";
      strengthText.textContent = "MEDIUM";
      strengthText.className = "text-center font-medium text-yellow-900";
    } else {
      strengthFill.className =
        "h-3 bg-green-500 transition-all duration-500 ease-in-out";
      strengthText.textContent = "STRONG";
      strengthText.className = "text-center font-medium text-green-900";
    }
  });
});
