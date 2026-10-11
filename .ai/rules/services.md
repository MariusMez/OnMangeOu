---
paths:
  - 'app/Services/**'
---

# Services

## External services behind a contract with a demo twin
Put each external side effect (LLM, headless browser) behind an interface in `app/Contracts` with a real implementation and a demo one that makes no network call; bind them in `AppServiceProvider` on `config('opticarte.mock')`.

## MenuAnalysisException for pipeline failures
When the analysis pipeline fails for a functional reason, throw `App\Exceptions\MenuAnalysisException` with a French message written for the restaurant owner; it is shown as-is on the analysis.

## Eloquent directly, starting with query()
No repository layer: simple reads query Eloquent in the controller, and reporting or aggregate queries live in a service. Start each query chain with `Model::query()`, not a static `Model::where()`.
