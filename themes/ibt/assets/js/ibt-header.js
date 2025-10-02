/**
 * IBT Header Search Toggle
 * Toggles the header search field open/closed.
 * Enhancement: if open and input has text → submit search form.
 */
document.addEventListener("DOMContentLoaded", () => {
  const header = document.querySelector(".ibt-header");
  const toggle = document.querySelector(".ibt-search-toggle");
  const field  = header?.querySelector(".ibt-search-field input[type='search']");
  const form   = field?.closest("form");

  if (!header || !toggle || !field || !form) return;

  // Future-proof: ensure this never acts like a submit button if moved into a <form>
  if (!toggle.getAttribute("type")) toggle.setAttribute("type", "button");

  // Toggle / submit behaviour on button click
  toggle.addEventListener("click", (e) => {
    e.preventDefault();

    const isOpen = header.classList.contains("ibt-search--open");

    if (!isOpen) {
      // Case 1: open search
      header.classList.add("ibt-search--open");
      toggle.setAttribute("aria-expanded", "true");
      field.focus();
      return;
    }

    const value = field.value.trim();

    if (value !== "") {
      // Case 2: already open + has text → submit (prefer requestSubmit to trigger validation/onsubmit)
      if (typeof form.requestSubmit === "function") {
        form.requestSubmit();
      } else {
        form.submit();
      }
      return;
    }

    // Case 3: open + empty → close
    header.classList.remove("ibt-search--open");
    toggle.setAttribute("aria-expanded", "false");
  });

  // Close on Escape key
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && header.classList.contains("ibt-search--open")) {
      header.classList.remove("ibt-search--open");
      toggle.setAttribute("aria-expanded", "false");
      toggle.focus();
    }
  });

  // Close if user clicks outside the header (but not on the toggle or its children)
  document.addEventListener("click", (e) => {
    const isOpen = header.classList.contains("ibt-search--open");
    if (
      isOpen &&
      !header.contains(e.target) &&
      !toggle.contains(e.target)
    ) {
      header.classList.remove("ibt-search--open");
      toggle.setAttribute("aria-expanded", "false");
    }
  });
});
