<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>


# Cashbacklive Partner

## Steps to setup project in local

* Clone the project in your computer from the repository

* Create `.env` file using this command -
  ```composer run post-root-package-install```

* Update database settings in `.env` file

* Install composer packages - ```composer install```

* Generate artisan key for project - ```composer run post-create-project-cmd```

* Run migration on the database along with seeder to feed dummy data - ```php artisan migrate:fresh --seed```

* Generate storage link - ```php artisan storage:link```

* Run the project server - ```php artisan serve```
