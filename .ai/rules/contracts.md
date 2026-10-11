---
paths:
  - 'app/Contracts/**'
---

# Contracts

## External services behind a contract with a demo twin
Put each external side effect (LLM, headless browser) behind an interface in `app/Contracts` with a real implementation and a demo one that makes no network call; bind them in `AppServiceProvider` on `config('opticarte.mock')`.
