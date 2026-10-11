---
paths:
  - 'app/**'
---

# App

## French text, English identifiers
Write user-facing text, PHPDoc and comments in French; keep identifiers (classes, methods, variables, routes, DB columns) in English. UI strings are written inline in French; there is no `lang/` or `__()` layer.

## Plain arrays, no DTOs
Pass structured data (extracted menus, reports, validated input) as plain arrays typed with PHPDoc array shapes, not DTO classes. Use native `array_*` functions and `foreach`; keep `collect()` for Eloquent results.

## Typed class constants
Declare class constants with a type, e.g. `private const int MIN_ITEMS = 3;`.
