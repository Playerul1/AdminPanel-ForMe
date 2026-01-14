/* SSA Core JS */

(function () {
    "use strict";

    // Small helpers
    window.SSA = window.SSA || {};

    SSA.qs = (sel, root = document) => root.querySelector(sel);
    SSA.qsa = (sel, root = document) => Array.from(root.querySelectorAll(sel));

    SSA.on = (el, evt, fn) => {
        if (!el) return;
        el.addEventListener(evt, fn, { passive: true });
    };

    // Simple toast (placeholder). Later we can upgrade.
    SSA.toast = (msg, type = "ok") => {
        let holder = SSA.qs("#ssaToastHolder");
        if (!holder) {
            holder = document.createElement("div");
            holder.id = "ssaToastHolder";
            holder.style.position = "fixed";
            holder.style.left = "12px";
            holder.style.right = "12px";
            holder.style.bottom = "14px";
            holder.style.display = "grid";
            holder.style.gap = "10px";
            holder.style.zIndex = "9999";
            document.body.appendChild(holder);
        }

        const el = document.createElement("div");
        el.className = "toast " + (type === "bad" ? "bad" : "ok");
        el.textContent = msg;

        holder.appendChild(el);
        setTimeout(() => {
            el.style.opacity = "0";
            el.style.transform = "translateY(6px)";
            el.style.transition = "all .18s ease";
            setTimeout(() => el.remove(), 240);
        }, 2600);
    };

    // Fetch wrapper (JSON)
    SSA.api = async (url, options = {}) => {
        const opt = Object.assign(
            {
                method: "GET",
                headers: {
                    "Accept": "application/json",
                },
                credentials: "same-origin",
            },
            options
        );

        // If posting JSON
        if (opt.body && typeof opt.body === "object" && !(opt.body instanceof FormData)) {
            opt.headers["Content-Type"] = "application/json";
            opt.body = JSON.stringify(opt.body);
        }

        const res = await fetch(url, opt);
        const text = await res.text();

        let data = null;
        try { data = JSON.parse(text); } catch (e) { /* ignore */ }

        if (!res.ok) {
            const msg = (data && (data.message || data.error)) ? (data.message || data.error) : ("Request failed: " + res.status);
            throw new Error(msg);
        }

        return data ?? text;
    };
})();
