## Q1 — How Facades work internally

A Facade in Laravel is not a truly static class — it only appears that way. When you call `Cache::put(...)`, PHP cannot find a real static method named `put()` on the Cache class. This triggers PHP's built-in `__callStatic()` magic method, which is defined in Laravel's base Facade class.

Inside `__callStatic()`, Laravel calls `getFacadeAccessor()`, which returns a string key such as `'cache'`. This key is used to look up the real service object in the IoC container via `app('cache')`. Once the real object — in this case `CacheManager` — is resolved, Laravel calls the original method on it and returns the result.

In short, every Facade call follows this chain:

```
Cache::put(...)  →  __callStatic()  →  getFacadeAccessor()  →  app('cache')  →  ->put(...)
```

The Facade itself does zero real work. It is purely a proxy — a shortcut that delegates to the container-resolved service behind the scenes.

---

## Q2 — Relation between Facade and Service Container

The Facade and the Service Container are completely dependent on each other — a Facade cannot work without the container. The container is the warehouse that stores every registered service. The Facade is simply a shortcut that reaches into that warehouse and fetches what it needs.

When Laravel boots, services are registered in the container with keys — for example, `'cache'` is bound to `CacheManager`, `'log'` is bound to `LogManager`. Each Facade declares which key it represents via `getFacadeAccessor()`. When you call the Facade, it passes that key to `app()` — the global container helper — which resolves and returns the real object.

```
Facade  →  accessor key  →  IoC Container  →  real service object
```

This means swapping the underlying implementation requires only one change in the container binding — every Facade call across your entire application automatically gets the new implementation. The Facade is the interface; the container controls what actually runs.
