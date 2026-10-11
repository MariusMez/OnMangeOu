---
paths:
  - 'app/Http/Controllers/**'
---

# Controllers

## Thin controllers with method-injected collaborators
Keep controllers thin: inject the Action or Service into the action method's signature, not the constructor, and delegate to it. Single-action controllers still use a named method (`show`, `index`), not `__invoke`.

## Eloquent directly, starting with query()
No repository layer: simple reads query Eloquent in the controller, and reporting or aggregate queries live in a service. Start each query chain with `Model::query()`, not a static `Model::where()`.
