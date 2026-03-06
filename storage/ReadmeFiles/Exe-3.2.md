# What is a Service in Laravel?

In Laravel, a service is a custom PHP class that contains business logic or reusable functionality for your application.

Think of services as a way to organize complex logic that doesn’t naturally belong in controllers, models, or routes.

## Why Use Services?

### 1. Keep Controllers Clean

Controllers should mainly handle:

- Receiving requests
- Validating data
- Returning responses

If you put all logic in the controller, it becomes messy and hard to maintain. Services move the logic out of controllers.

### 2. Reusability

A service can be used in multiple places:

- Controllers
- Jobs
- Console commands
- Other services

For example, a `DiscountService` can be used in `ProductController`, `OrderController`, or even in an `InvoiceService`.

### 3. Separation of Concerns

Services help separate:

- **Business logic** → inside the service
- **Request handling** → inside the controller
- **Database interactions** → inside models

This makes the code easier to read, test, and maintain.

### 4. Easier Testing

You can unit test services independently of controllers.  
This improves code quality and reliability.

## Example Analogy

Think of your Laravel app like a restaurant:

| Part       | Role                                                             |
| ---------- | ---------------------------------------------------------------- |
| Controller | Waiter – takes orders (HTTP requests) and serves food (response) |
| Model      | Ingredients – raw data stored in the kitchen (database)          |
| Service    | Chef – prepares the dish (business logic / processing)           |

By separating responsibilities:

- Waiter doesn’t cook
- Chef focuses on cooking
- Ingredients are managed separately
