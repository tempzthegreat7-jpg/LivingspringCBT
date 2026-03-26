(function () {
  let redirected = false;
  const csrfMeta = document.querySelector("meta[name='csrf-token']");
  const csrfToken = csrfMeta ? String(csrfMeta.getAttribute("content") || "") : "";

  const maybeRedirectInactive = (payload) => {
    if (redirected) {
      return;
    }

    if (payload && payload.inactive && payload.redirect) {
      redirected = true;
      window.location.href = payload.redirect;
    }
  };

  const ping = () => {
    fetch("/presence/ping", {
      method: "POST",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
        "X-CSRF-Token": csrfToken,
      },
      keepalive: true,
    })
      .then((response) => response.json())
      .then((payload) => {
        maybeRedirectInactive(payload);
      })
      .catch(() => {});
  };

  ping();
  const intervalId = window.setInterval(ping, 10000);

  document.addEventListener("visibilitychange", () => {
    if (document.visibilityState === "visible") {
      ping();
    }
  });

  window.addEventListener("beforeunload", () => {
    window.clearInterval(intervalId);
  });
})();
