## Already Implemented

#### in the Router part when i create the post route and test it , i face this problem into it

#### then i solve it by make the changes into the `bootstrap/app.php` file

```php
$middleware->validateCsrfTokens(except: [
    '/post'
]);
```
