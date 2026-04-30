# Laravel HTTP Client Helpers

## 🔹 `$response->json()` vs `$response->collect()`

### `$response->json()`

- Returns **array**
- Simple PHP data
- No Laravel magic

```php
$data = $response->json();
$response->collect()
```

Returns Laravel Collection,
You can use powerful methods like:\
filter()\
map()\
pluck()\
$products = $response->collect();

```
$cheap = $products->where('price', '<', 50);
```

👉 Laravel docs confirm:

json() → array
collect() → Collection
🔹 successful() vs ok()

This is where many beginners mess up.

✅ successful()\
Returns true for ANY 2xx status
Covers:\
200\
201\
204\
etc.

```
$response->successful();
```

✅ ok()
Returns true ONLY for 200
Strict check\
$response->ok();

👉 From Laravel:

successful() → checks 2xx range
ok() → checks exactly 200
