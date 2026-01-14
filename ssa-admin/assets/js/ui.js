/* SSA UI JS (sidebar drawer) */

(function () {
    "use strict";

    const app = document.getElementById("ssaApp");
    const sidebar = document.getElementById("ssaSidebar");
    const overlay = document.getElementById("ssaOverlay");
    const menuBtn = document.getElementById("ssaMenuBtn");

    const open = () => {
        if (!app) return;
        app.classList.add("is-sidebar-open");
        if (overlay) overlay.setAttribute("aria-hidden", "false");
    };

    const close = () => {
        if (!app) return;
        app.classList.remove("is-sidebar-open");
        if (overlay) overlay.setAttribute("aria-hidden", "true");
    };

    if (menuBtn) {
        menuBtn.addEventListener("click", (e) => {
            e.preventDefault();
            if (app.classList.contains("is-sidebar-open")) close();
            else open();
        });
    }

    if (overlay) {
        overlay.addEventListener("click", (e) => {
            e.preventDefault();
            close();
        });
    }

    // ESC close
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") close();
    });

    // If screen resized to desktop, ensure overlay closed
    window.addEventListener("resize", () => {
        if (window.innerWidth > 980) close();
    });
})();
