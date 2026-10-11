---
paths:
  - 'app/Enums/**'
---

# Enums

## Enums own their labels
Make enums string-backed and give them a French `label()` built with `match`, plus presentation helpers (such as `chipTone()`) and a static `values()` where a schema needs the raw list. Views call these methods instead of mapping values themselves.
