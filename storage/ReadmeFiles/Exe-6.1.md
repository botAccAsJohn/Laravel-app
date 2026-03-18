| Feature                     | `register()`         | `boot()`                  |
| --------------------------- | -------------------- | ------------------------- |
| When it runs                | Early                | After everything is ready |
| Purpose                     | Bind services        | Use services              |
| Safe to use other services? | ❌ No                | ✅ Yes                    |
| Common use                  | `$this->app->bind()` | Routes, events, views     |
| Think of it as              | Setup                | Execution                 |
