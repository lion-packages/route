# 🦁 Lion-Route

<p align="center">
  <a href="https://dev.lion-packages.com/docs/library/content" target="_blank">
    <img 
        src="https://github.com/lion-packages/framework/assets/56183278/60871c9f-1c93-4481-8c1e-d70282b33254"
        width="450" 
        alt="Lion-Packages Logo"
    >
  </a>
</p>

<p align="center">
  <a href="https://packagist.org/packages/lion/route">
    <img src="https://poser.pugx.org/lion/route/v" alt="Latest Stable Version">
  </a>
  <a href="https://packagist.org/packages/lion/route">
    <img src="https://poser.pugx.org/lion/route/downloads" alt="Total Downloads">
  </a>
  <a href="https://github.com/lion-packages/route/blob/main/LICENSE">
    <img src="https://poser.pugx.org/lion/route/license" alt="License">
  </a>
  <a href="https://www.php.net/">
    <img src="https://poser.pugx.org/lion/route/require/php" alt="PHP Version Require">
  </a>
</p>

🚀 **Lion-Route** This library has quick router usage with regular expressions.

---

## 📖 Features

✔️ Supports post, get, put, delete, options, and match methods.  
✔️ Middleware Support.  
✔️ Support with route group.  

---

## 📦 Installation

Install the route using **Composer**:

```bash
composer require lion/route lion/exceptions lion/request lion/security lion/dependency-injection
```

## Usage Example

```php
<?php

declare(strict_types=1);

use Lion\Route\Route;
use App\Http\Controllers\UsersController;

Route::init();

Route::get('users', function(UsersController $usersController): mixed {
    return $usersController->method();
});

Route::dispatch();
```

## 📝 License

The <strong>route</strong> is open-sourced software licensed under the [MIT License](https://github.com/lion-packages/route/blob/main/LICENSE).
