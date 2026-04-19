<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

কোডটি সফলভাবে আপনার GitHub রিপোজিটরিতে পুশ করা হয়েছে! আপনি এখন [GitHub-এ আপনার রিপোজিটরি](https://github.com/MasudDeveloper/rootva_laravel_admin.git) চেক করে দেখতে পারেন।

এখন **Shared Hosting**\-এ এটি পুল (Pull) করার জন্য নিচের ধাপগুলো অনুসরণ করুন:

### ১. SSH বা Terminal ব্যবহার করে (সবচেয়ে সহজ):

যদি আপনার হোস্টিংয়ে SSH বা cPanel Terminal এক্সেস থাকে, তবে নিচের কমান্ডগুলো দিন:

*   bashcd public\_html/your-project-folder
    
*   bashgit clone https://github.com/MasudDeveloper/rootva\_laravel\_admin.git ._(মনে রাখবেন, শেষ একটা ডট . আছে যাতে বর্তমান ফোল্ডারেই কোডগুলো আসে)_
    

### ২. সার্ভারে Laravel সেটআপ করার কিছু প্রয়োজনীয় কাজ:

কোড পুল করার পর আপনাকে সার্ভারে এই কমান্ডগুলো দিতে হবে:

*   bashcomposer install --optimize-autoloader --no-dev
    
*   **.env ফাইল তৈরি:** সার্ভারে পিসির মতো .env ফাইল থাকে না। তাই আপনাকে .env.example কপি করে একটি .env ফাইল বানাতে হবে এবং সেখানে সার্ভারের ডাটাবেজ ইউজার ও পাসওয়ার্ড দিতে হবে।
    
*   bashphp artisan key:generate
    
*   bashphp artisan migrate
    
*   bashphp artisan storage:link
    

**আপনার কি সার্ভারে SSH বা Terminal এক্সেস আছে?** যদি না থাকে, তবে আপনি cPanel-এর **"Git Version Control"** টুলটি ব্যবহার করতে পারেন। সেটি কিভাবে করতে হয় তা জানতে চাইলে আমাকে বলতে পারেন।
