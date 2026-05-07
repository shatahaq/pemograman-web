/*
  ElektraRent — Production JavaScript
  Handles UI interactions: mobile nav, modals, icons, and toast notifications.
  All authentication and data operations are handled server-side via PHP sessions.
*/
(function () {
  "use strict";

  /* ──────── Lucide Icons ──────── */
  function initIcons() {
    if (window.lucide && typeof window.lucide.createIcons === "function") {
      window.lucide.createIcons({ attrs: { "stroke-width": 1.8 } });
    }
  }

  /* ──────── HTML Escape ──────── */
  function escapeHtml(value) {
    return String(value)
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  /* ──────── Format Rupiah ──────── */
  function formatRupiah(value) {
    return new Intl.NumberFormat("id-ID", {
      style: "currency",
      currency: "IDR",
      maximumFractionDigits: 0,
    }).format(Number(value) || 0);
  }

  /* ──────── Toast Notification ──────── */
  function showToast(message, type) {
    const toastType = type || "info";
    let toast = document.querySelector("[data-toast]");

    if (!toast) {
      toast = document.createElement("div");
      toast.className = "toast";
      toast.setAttribute("data-toast", "true");
      document.body.appendChild(toast);
    }

    const iconName =
      toastType === "danger"
        ? "alert-triangle"
        : toastType === "success"
          ? "check-circle-2"
          : "radio-tower";
    toast.className = `toast toast-${toastType}`;
    toast.innerHTML = `
      <div class="toast-content">
        <span class="toast-icon"><i data-lucide="${iconName}" class="h-4 w-4"></i></span>
        <div>
          <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--color-brick)]">System Message</p>
          <p class="mt-1 text-sm font-bold text-[var(--color-navy)]">${escapeHtml(message)}</p>
        </div>
      </div>
    `;

    window.clearTimeout(showToast.hideTimer);
    toast.classList.add("show");
    initIcons();

    showToast.hideTimer = window.setTimeout(function () {
      toast.classList.remove("show");
    }, 3400);
  }

  /* ──────── Mobile Navigation ──────── */
  function setupMobileNavigation() {
    document.querySelectorAll("[data-nav-toggle]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        const target = document.querySelector(btn.getAttribute("data-target"));
        if (!target) return;
        target.classList.toggle("hidden");
        btn.setAttribute("aria-expanded", String(!target.classList.contains("hidden")));
      });
    });
  }

  /* ──────── Admin Sidebar ──────── */
  function setupAdminSidebar() {
    const sidebar = document.querySelector("#adminSidebar");
    const overlay = document.querySelector("#adminSidebarOverlay");

    document.querySelectorAll("[data-sidebar-toggle]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        if (!sidebar || !overlay) return;
        sidebar.classList.toggle("open");
        overlay.classList.toggle("open");
        btn.setAttribute("aria-expanded", String(sidebar.classList.contains("open")));
      });
    });

    if (overlay && sidebar) {
      overlay.addEventListener("click", function () {
        sidebar.classList.remove("open");
        overlay.classList.remove("open");
      });
    }
  }

  /* ──────── Modal (legacy support) ──────── */
  function openModal(sel) {
    const m = document.querySelector(sel);
    if (!m) return;
    m.classList.add("open");
    m.setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
    initIcons();
    const fi = m.querySelector("input, select, textarea, button");
    if (fi) setTimeout(function () { fi.focus(); }, 80);
  }

  function closeModal(el) {
    if (!el) return;
    el.classList.remove("open");
    el.setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
  }

  function setupModals() {
    document.querySelectorAll("[data-modal-open]").forEach(function (btn) {
      btn.addEventListener("click", function () { openModal(btn.getAttribute("data-modal-open")); });
    });
    document.querySelectorAll("[data-modal-close]").forEach(function (btn) {
      btn.addEventListener("click", function () { closeModal(btn.closest(".modal-backdrop")); });
    });
    document.querySelectorAll(".modal-backdrop").forEach(function (m) {
      m.addEventListener("click", function (e) { if (e.target === m) closeModal(m); });
    });
    document.addEventListener("keydown", function (e) {
      if (e.key !== "Escape") return;
      document.querySelectorAll(".modal-backdrop.open").forEach(function (m) { closeModal(m); });
    });
  }

  /* ──────── Password Toggles ──────── */
  function setupPasswordToggles() {
    document.querySelectorAll("[data-password-toggle]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        const input = document.querySelector(btn.getAttribute("data-target"));
        if (!input) return;
        const show = input.type === "password";
        input.type = show ? "text" : "password";
        btn.setAttribute("aria-label", show ? "Sembunyikan password" : "Tampilkan password");
        btn.innerHTML = show
          ? '<i data-lucide="eye-off" class="h-4 w-4"></i>'
          : '<i data-lucide="eye" class="h-4 w-4"></i>';
        initIcons();
      });
    });
  }

  /* ──────── Current Year ──────── */
  function setupCurrentYear() {
    document.querySelectorAll("[data-current-year]").forEach(function (el) {
      el.textContent = String(new Date().getFullYear());
    });
  }

  /* ──────── Init ──────── */
  document.addEventListener("DOMContentLoaded", function () {
    setupMobileNavigation();
    setupAdminSidebar();
    setupModals();
    setupPasswordToggles();
    setupCurrentYear();
    initIcons();
  });

  /* ──────── Global API ──────── */
  window.ElektraRent = {
    closeModal: closeModal,
    formatRupiah: formatRupiah,
    initIcons: initIcons,
    openModal: openModal,
    showToast: showToast,
  };
})();
