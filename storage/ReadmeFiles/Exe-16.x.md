# `input()` vs `all()` vs Property Access in Laravel

All three retrieve data from the HTTP request, but they behave differently.

---

## `input()`

```php
$name = $request->input('name');
Retrieves a single value by key
Works with query string + request body (GET & POST)
Supports dot notation for nested data
Supports a default value if key is missing
// Default value
$name = $request->input('name', 'Guest');

// Nested data (dot notation)
$city = $request->input('address.city');

// From a JSON body
$token = $request->input('user.token');
```

## `all()`

```php
$data = $request->all();
Returns all input data as an array
Includes query string + request body together
Commonly used to pass all data to a model or validator
// Typical usage
User::create($request->all());

// With validation
$validated = $request->validate([...]);
// prefer validated() over all() for safety
```

⚠️ Avoid using all() directly with create() without mass assignment protection — use validated() or only() instead.

```php
Property Access ($request->name)
$name = $request->name;
Uses Laravel's magic __get() method under the hood
Shorthand for input() — behaves identically
Clean and readable, but less explicit
Also works with route parameters as fallback
$request->name;      // same as $request->input('name')
$request->address;   // same as $request->input('address')
```

| Feature          | `input()`    | `all()`            | Property Access |
| ---------------- | ------------ | ------------------ | --------------- |
| Returns          | Single value | All values (array) | Single value    |
| Default value    | ✅ Yes       | ❌ No              | ❌ No           |
| Dot notation     | ✅ Yes       | ❌ No              | ❌ No           |
| Reads JSON body  | ✅ Yes       | ✅ Yes             | ✅ Yes          |
| Explicit & clear | ✅ Yes       | ✅ Yes             | ❌ Magic method |

---

---

# `store()` vs `storeAs()` in Laravel

Both methods are used to save uploaded files, but they differ in how the filename is handled.

---

## `store()`

```php
$path = $request->file('avatar')->store('avatars');
Laravel auto-generates a unique filename (UUID-based, e.g. 5x8f2a...jpg)
You only specify the directory
Prevents filename collisions automatically
Returns the full path: avatars/5x8f2a3b9c1d.jpg

// You can also pass a disk:

$path = $request->file('avatar')->store('avatars', 's3');
```

## `storeAs()`

```php
$path = $request->file('avatar')->storeAs('avatars', 'profile.jpg');
You manually define the filename
You specify both the directory and the filename
Returns the full path: avatars/profile.jpg

// With a custom disk:

$path = $request->file('avatar')->storeAs('avatars', 'profile.jpg', 's3');
```

| Feature           | `store()`             | `storeAs()`       |
| ----------------- | --------------------- | ----------------- |
| Filename          | Auto-generated (UUID) | You define it     |
| Collision risk    | None                  | Possible          |
| Control over name | ❌                    | ✅                |
| Use case          | General uploads       | When name matters |
