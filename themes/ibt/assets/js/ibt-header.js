/**
 * IBT Header Search Toggle
 * Toggles the header search field open/closed.
 */
document.addEventListener("DOMContentLoaded", () => {
  const header = document.querySelector(".ibt-header");
  const toggle = document.querySelector(".ibt-search-toggle");
  const field  = header?.querySelector(".ibt-search-field input[type='search']");

  if (!header || !toggle || !field) return;

  // Toggle open/close on button click
  toggle.addEventListener("click", (e) => {
    e.preventDefault();
    const isOpen = header.classList.toggle("ibt-search--open");
    toggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
    if (isOpen) {
      field.focus();
    }
  });

  // Close on Escape key
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && header.classList.contains("ibt-search--open")) {
      header.classList.remove("ibt-search--open");
      toggle.setAttribute("aria-expanded", "false");
      toggle.focus();
    }
  });

  // Close if user clicks outside
  document.addEventListener("click", (e) => {
    if (
      header.classList.contains("ibt-search--open") &&
      !header.contains(e.target) &&
      e.target !== toggle
    ) {
      header.classList.remove("ibt-search--open");
      toggle.setAttribute("aria-expanded", "false");
    }
  });
});
