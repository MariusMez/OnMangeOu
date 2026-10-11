---
paths:
  - 'resources/views/**'
---

# Views

## Format prices with App\Support\Price
Format prices with `App\Support\Price`: `Price::euro()` in the interface, `Price::menu()` (no currency symbol) on the optimised menu and PDF.

## Anonymous Blade components and vanilla JS data hooks
Build reusable UI as anonymous Blade components (`@props`) in `resources/views/components`. Page behaviour goes in `resources/js/app.js` as vanilla JS hooked on `data-*` attributes; no Alpine, no inline `<script>`.
