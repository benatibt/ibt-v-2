/**
 * IBT Header Search Toggle
 * ---------------------------------------------
 * WHY: Controls the header search field visibility and submission logic.
 * WHAT IT DOES: Toggles `.is-open` class on `.ibt-header-search`; 
 * opens/closes search, auto-submits if open and input has text.
 * HOW TO MAINTAIN: Keep selectors in sync with header markup. 
 * ---------------------------------------------
 */

document.addEventListener("DOMContentLoaded", () => {
  const header  = document.querySelector(".ibt-header");
  const wrapper = header?.querySelector(".ibt-header-search");
  const toggle  = header?.querySelector(".ibt-header-search-toggle");
  const field   = wrapper?.querySelector(".ibt-header-search-field input[type='search']");
  const form    = field?.closest("form");
  const OPEN_CLASS = "is-open";

  // Safety check: stop if any required element missing
  if (!header || !wrapper || !toggle || !field || !form) return;

  // Defensive: ensure the toggle button never submits if placed inside a form
  if (!toggle.getAttribute("type")) toggle.setAttribute("type", "button");

  // Helper: current open state
  const isOpen = () => wrapper.classList.contains(OPEN_CLASS);

  // --- Toggle or submit on click ---
  toggle.addEventListener("click", (e) => {
    e.preventDefault();

    if (!isOpen()) {
      // Case 1: Closed → Open search
      wrapper.classList.add(OPEN_CLASS);
      toggle.setAttribute("aria-expanded", "true");
      field.focus();
      return;
    }

    const value = field.value.trim();

    if (value !== "") {
      // Case 2: Open + has text → submit form
      if (typeof form.requestSubmit === "function") {
        form.requestSubmit();
      } else {
        form.submit();
      }
      return;
    }

    // Case 3: Open + empty → Close search
    wrapper.classList.remove(OPEN_CLASS);
    toggle.setAttribute("aria-expanded", "false");
  });

  // --- Close on Escape key ---
  header.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && isOpen()) {
      wrapper.classList.remove(OPEN_CLASS);
      toggle.setAttribute("aria-expanded", "false");
      toggle.focus();
    }
  });

  // --- Close if user clicks outside the header search area ---
  document.addEventListener("click", (e) => {
    if (isOpen() && !wrapper.contains(e.target) && !toggle.contains(e.target)) {
      wrapper.classList.remove(OPEN_CLASS);
      toggle.setAttribute("aria-expanded", "false");
    }
  });

  // Optional: Custom event hook for analytics or UI sync
  const dispatchToggleEvent = () => {
    const event = new CustomEvent("ibt:searchToggled", { detail: { open: isOpen() } });
    wrapper.dispatchEvent(event);
  };

  // Fire event whenever state changes
  const observer = new MutationObserver(dispatchToggleEvent);
  observer.observe(wrapper, { attributes: true, attributeFilter: ["class"] });
});
