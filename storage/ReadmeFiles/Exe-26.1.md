# Screenshot of route:list showing both admin and customer routes

```
php artisan route:list
```

GET|HEAD / .....................................................................................  
 GET|HEAD api/download ..........................................................................  
 GET|HEAD api/exception .........................................................................  
 GET|HEAD api/json ..............................................................................  
 GET|HEAD api/modifyJson ......................................................... api.modifyJson  
 GET|HEAD api/redirect ..........................................................................  
 GET|HEAD api/redis .............................................................................  
 GET|HEAD api/redis-keys ........................................................................  
 GET|HEAD api/string ............................................................................  
 GET|HEAD api/test-session ......................................................................  
 GET|HEAD api/user ..............................................................................  
 GET|HEAD api/view ..............................................................................  
 GET|HEAD cart ................................................ cart.index › CartController@index  
 POST cart/add/{productId} .................................... cart.add › CartController@add  
 POST cart/clear .......................................... cart.clear › CartController@clear  
 POST cart/decrement/{productId} .................. cart.decrement › CartController@decrement  
 POST cart/remove/{productId} ........................... cart.remove › CartController@remove  
 GET|HEAD confirm-password ........... password.confirm › Auth\ConfirmablePasswordController@show  
 POST confirm-password ............................. Auth\ConfirmablePasswordController@store  
 GET|HEAD dashboard ................................................................... dashboard  
 POST email/verification-notification verification.send › Auth\EmailVerificationNotification…  
 GET|HEAD export-products ................... products.export › Product2Controller@exportProducts  
 GET|HEAD forgot-password ............ password.request › Auth\PasswordResetLinkController@create  
 POST forgot-password ............... password.email › Auth\PasswordResetLinkController@store  
 GET|HEAD generate-link/{id} ....................................................................  
 GET|HEAD login .............................. login › Auth\AuthenticatedSessionController@create  
 POST login ....................................... Auth\AuthenticatedSessionController@store  
 POST logout ........................... logout › Auth\AuthenticatedSessionController@destroy  
 GET|HEAD logs ............................................. logs.index › Product2Controller@logs  
 GET|HEAD orders ........................................... orders.index › OrderController@index  
 POST orders ........................................... orders.store › OrderController@store  
 GET|HEAD orders/create .................................. orders.create › OrderController@create  
 GET|HEAD orders/{order} ..................................... orders.show › OrderController@show  
 PUT|PATCH orders/{order} ................................. orders.update › OrderController@update  
 DELETE orders/{order} ............................... orders.destroy › OrderController@destroy  
 GET|HEAD orders/{order}/edit ................................ orders.edit › OrderController@edit  
 PUT password ............................. password.update › Auth\PasswordController@update  
 POST products .................................... products.store › Product2Controller@store  
 GET|HEAD products .................................... products.index › Product2Controller@index  
 GET|HEAD products/create ........................... products.create › Product2Controller@create  
 PUT|PATCH products/{product} ........................ products.update › Product2Controller@update  
 DELETE products/{product} ...................... products.destroy › Product2Controller@destroy  
 GET|HEAD products/{product} ............................ products.show › Product2Controller@show  
 GET|HEAD products/{product}/edit ....................... products.edit › Product2Controller@edit  
 GET|HEAD profile ......................................... profile.edit › ProfileController@edit  
 PATCH profile ..................................... profile.update › ProfileController@update  
 DELETE profile ................................... profile.destroy › ProfileController@destroy  
 GET|HEAD recently-viewed ......................... recently.index › RecentlyViewController@index  
 POST recently-viewed/clear ................... recently.clear › RecentlyViewController@clear  
 GET|HEAD register .............................. register › Auth\RegisteredUserController@create  
 POST register .......................................... Auth\RegisteredUserController@store  
 POST reset-password ...................... password.store › Auth\NewPasswordController@store  
 GET|HEAD reset-password/{token} ............. password.reset › Auth\NewPasswordController@create  
 GET|HEAD storage/{path} .......................................................... storage.local  
 PUT storage/{path} ................................................... storage.local.upload  
 GET|HEAD unsubscribe/{user} ........................................................ unsubscribe  
 GET|HEAD up ....................................................................................  
 GET|HEAD verify-email ............. verification.notice › Auth\EmailVerificationPromptController  
 GET|HEAD verify-email/{id}/{hash} ............. verification.verify › Auth\VerifyEmailController

## php artisan list

The `php artisan list` command displays all available Artisan commands in a Laravel application. It provides a comprehensive overview of built-in and custom commands, including their names and descriptions. This helps developers quickly discover functionality, understand command usage, and navigate the CLI tools efficiently during development and debugging tasks.

## php artisan route:list

The `php artisan route:list` command shows all registered routes in the application, including web, API, admin, and customer routes. It displays useful details like HTTP methods, URIs, route names, controllers, and middleware. This command helps developers debug routing issues and understand how requests are handled within the Laravel application structure.

## php artisan db:show

The `php artisan db:show` command provides detailed information about the application's database. It typically includes database type, size, tables, and other metadata. This command is useful for quickly inspecting database structure and status without needing external tools, helping developers monitor and manage database-related aspects directly from the command line.

## php artisan cache:clear

The `php artisan cache:clear` command removes all cached data from the application. Laravel uses caching to improve performance, but outdated cache can cause issues. This command ensures fresh data is loaded by clearing stored cache, making it especially useful during development or after configuration, route, or view changes.

| Command                  | When to Use                                                                                                                                                 | Purpose                                                                                                           |
| ------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------- |
| php artisan cache:clear  | Use when application data, views, or routes behave unexpectedly due to old cached data. Helpful during development or after updates affecting runtime data. | Clears the application cache, ensuring fresh data is loaded and preventing issues caused by stale cached content. |
| php artisan config:clear | Use when environment variables (.env) or configuration files are changed but not reflected in the application. Common after deployment or config updates.   | Removes the cached configuration file so Laravel reloads updated config settings from source files.               |
