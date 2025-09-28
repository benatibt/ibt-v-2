# IBT Header Design
Design and implementation notes for the Islands Book Trust WordPress theme header.
This file records the structure, required classes, and key decisions so future
developers (or future me) can create new header variants with confidence.

---

## Overview

The IBT header is a **two-row sticky band** with a width-constrained inner grid.
It adapts to viewport width *and* height to stay usable on small devices and
landscape phones.

| LOGO < |< DESKTOP SITE TITLE___________ UTIL: search acct cart >|
|        |< MOBILE SITE TITLE__________ NAV_NAV_NAV_NAV_NAV_NAV_ >|


`<` or `>` indicate left/right justification.
* Desktop / wide viewports: desktop site title shows in the top row. 
* Narrow viewports: desktop title hides, mobile title shows in the bottom row next to the nav. 
* Navigation collapses to a hamburger early on narrow width.

* Sticky is not activated on small height viewports
* Sticky requires :has() support. Browsers without it simply show a normal scrolling header.

---


## CSS Class Reference (final)

These **CSS classes** are the only ones required for the production header.
All other layout should be handled with block settings or `theme.json`.

| Class | Applied To | Purpose |
|------|------------|--------|
| **ibt-header** | Outer full-width header Group | Full-bleed colour band and root-padding clamp. |
| **ibt-sticky** | Same outer Group | Behaviour flag. Enables sticky via the `:has()` wrapper rule. Remove to disable sticky in a variant. |
| **ibt-header-inner** | Inner width container Group | Sets `max-width` and defines the 2-column × 2-row grid. |
| **ibt-title--desktop** | Site Title block (row 1) | Visible on wide/tall viewports. Hidden on narrow/short. |
| **ibt-title--mobile** | Site Title block (row 2) | Hidden on wide/tall. Shown on narrow/short. |
| **ibt-header-nav** | Navigation block | Single hook for header-nav styling **and** responsive behaviour (font/spacing scaling, hamburger triggers, etc.). |


---

## Design Principles

1. **theme.json first** – Colours, fonts, spacing tokens, and hover colours live in `theme.json`.
2. **CSS only for global behaviours** – Sticky positioning (`:has()`), height/width media queries, and advanced selectors.
3. **Template/Template-part styles** – Visual composition (grid placement, flex settings) edited in the Site Editor, not hard-coded HTML.
4. **Dynamic CSS** – Use media queries for responsive visibility and breakpoints.
5. **JavaScript last** – Only if no other method exists (e.g. mobile search modal and TBC “back to top” button).

---

## Layout Summary

The header uses a **2-column × 2-row CSS Grid** inside `.ibt-header-inner`:

| Area | Grid Column | Grid Row |
|------|-------------|---------|
| Logo | 1 | 1–2 (spans both rows) |
| Right Column Wrapper | 2 | 1–2 (contains both rows) |

Inside the **Right Column Wrapper**:
* **Row 1** – Desktop title + utilities (flex layout)
* **Row 2** – Mobile title + primary navigation (flex layout)

* The Logo spans both rows to keep vertical alignment consistent. **Logo must work at small size.**
* Desktop Title hides at the same breakpoint where the Navigation collapses. 
* Mobile Title appears in row 2 when Desktop Title hides. 
* Utility icons remain in the top right and stay visible at all breakpoints.

---

## Responsive Behaviour


* **Tall and Wide Viewports (default)** 
  – Sticky enabled 
  – Desktop Title visible 
  – Mobile Title hidden 
  – Nav expanded

* **Medium Viewport Width (~≤1100 px width)** 
Intermediate state to delay menu collapse as far as feasible
  – Reduce navigation and site title font size via CSS tokens
  – Compact padding and smaller logo size via CSS tokens

* **Narrow Viewport Width (~≤800 px width)** 
  – Nav forced to hamburger (`ibt-nav-collapse-early`) 
  – Desktop Title hidden 
  – Mobile Title visible 
  – Maintain medium viewport padding

* **Short Viewports (~≤600 px height)** 
Seperate behaviour on top of width adaption
  – Sticky disabled 



---

## Change History

*2025-09-28* – Initial design document created.

---

## Notes for Future Variants

* **Non-sticky header** (e.g. shop): simply remove `ibt-sticky` from the outer Group.
* **Different colour scheme**: adjust `theme.json` palette or apply a new style variation.
* **Alternate navigation layout**: retain the class list so the CSS helpers (padding, breakpoints) continue to work.

---

