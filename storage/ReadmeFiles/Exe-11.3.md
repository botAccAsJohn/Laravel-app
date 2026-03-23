# Dependency Injection in Laravel

## 🔹 Constructor Injection

Inject in constructor

```php
use App\Services\ProductService;

class ProductController extends Controller
{
    protected $service;

    public function __construct(ProductService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $products = $this->service->getAll();

        return view('products.index', compact('products'));
    }
}

```

### 💡 When to use:

When service is needed in many methods

👉 Think:

“Give me this dependency once, I’ll reuse it everywhere”

## 🔹 Method Injection

Inject directly in method

```php

public function index(ProductService $service)
{
    return $service->getAll();
}
```

💡 When to use:

Only needed in one or few methods

👉 Think:

“I need this only here, don’t store it globally”

| Feature     | Constructor Injection    | Method Injection      |
| ----------- | ------------------------ | --------------------- |
| Scope       | Whole class              | Single method         |
| Reusability | High                     | Low                   |
| Clean code  | Cleaner for shared logic | Cleaner for small use |
| Memory      | Slightly more            | Efficient             |
