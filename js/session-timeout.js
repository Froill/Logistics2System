 (() => {
      const INACTIVITY_LIMIT = 600; // 5 minutes = 300 seconds
      const WARNING_BEFORE = 30;
      const logoutUrl = "logout.php";

      let inactivityTimer, warningTimer, countdownTimer;

      function resetInactivity() {
        clearTimeout(inactivityTimer);
        clearTimeout(warningTimer);
        clearInterval(countdownTimer);
        if (document.getElementById("timeoutModal").open) {
          document.getElementById("timeoutModal").close();
        }
        startTimers();
      }

      function startTimers() {
        inactivityTimer = setTimeout(() => {
          window.location.href = logoutUrl;
        }, INACTIVITY_LIMIT * 1000);

        warningTimer = setTimeout(showWarning, (INACTIVITY_LIMIT - WARNING_BEFORE) * 1000);
      }

      function showWarning() {
        let timeLeft = WARNING_BEFORE;
        const countdownEl = document.getElementById("countdown");
        countdownEl.textContent = timeLeft;
        document.getElementById("timeoutModal").showModal();

        countdownTimer = setInterval(() => {
          timeLeft--;
          countdownEl.textContent = timeLeft;
          if (timeLeft <= 0) {
            clearInterval(countdownTimer);
            window.location.href = logoutUrl;
          }
        }, 1000);
      }

      ["click", "mousemove", "keydown", "scroll", "touchstart"].forEach(evt => {
        document.addEventListener(evt, resetInactivity, {
          passive: true
        });
      });

      document.getElementById("stayLoggedIn").addEventListener("click", e => {
        e.preventDefault();
        resetInactivity();
      });

      startTimers();
    })();