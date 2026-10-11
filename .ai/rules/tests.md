---
paths:
  - 'tests/**'
---

# Tests

## Tests run in demo mode
Feature tests run the real flow in demo mode: `config(['opticarte.mock' => true])` and `Storage::fake('local')`, so the sync queue runs the whole pipeline. Create analyses by posting through the app or with `Analysis::create()`, and fake AI agents with `Agent::fake()` instead of Mockery.
