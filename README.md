<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Water station workflows

Run `php artisan migrate` after pulling changes to create the supplier receipt and repeat-order tables. Repeat orders use cash payment and are not charged online automatically; customers can pause or skip them from **Repeat orders**.

The repeat-order job runs daily at 6:00 AM using Laravel's scheduler. For local development, run `php artisan schedule:work`. In production, configure the platform scheduler to run `php artisan schedule:run` every minute. On Wasmer Edge, configure this as a scheduled job.

## Deploying on Wasmer Edge

Wasmer Edge detects Laravel applications and installs Composer dependencies during deployment. Configure these values in the Wasmer app's environment settings rather than committing them:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL` set to the public HTTPS URL
- `APP_KEY` set to a key generated once for this deployment; keep it stable between releases
- `DB_CONNECTION=mysql` when using Wasmer's managed database

Wasmer provides database settings as `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USERNAME`, and `DB_PASSWORD`. The Laravel MySQL configuration accepts `DB_NAME` as a fallback for `DB_DATABASE`, and selects MySQL by default when `DB_HOST` is present. Keep SQLite for local development only; Wasmer Edge instances are stateless by default.

After configuring the database, run migrations from a Wasmer remote session with `php artisan migrate --force --no-interaction`. The `--force` flag prevents Laravel's production confirmation prompt; `--no-interaction` avoids requiring a terminal. Do not run migrations as an interactive startup command.

Ensure the deployment build runs `npm run build` so Laravel's Vite manifest is present in `public/build`. Configure Wasmer's scheduled job to run `php artisan schedule:run` every minute if you rely on repeat orders or other Laravel scheduled tasks.

Set `PAYMENTS_GATEWAY=mock` until PayMongo is configured. For PayMongo, set `PAYMENTS_GATEWAY=paymongo` and provide `PAYMONGO_SECRET_KEY` and `PAYMONGO_WEBHOOK_SECRET` using Wasmer's secret settings. Never commit these values or a production `.env` file.

## QR Ph payments with PayMongo

The mock QR Ph checkout remains enabled by default. To use PayMongo Hosted Checkout, set `PAYMENTS_GATEWAY=paymongo`, set `APP_URL` to your public HTTPS domain, add your PayMongo secret key to `PAYMONGO_SECRET_KEY`, and add the webhook signing secret to `PAYMONGO_WEBHOOK_SECRET` in `.env`. Keep both keys private and use test-mode credentials until you are ready to accept live payments. PayMongo must have QR Ph enabled for the key's mode.

The customer is redirected to PayMongo's hosted QR Ph checkout, where they can scan the presented QR code with a supported banking or e-wallet app and follow the provider's payment instructions.

In the PayMongo dashboard, register `https://your-domain/webhooks/paymongo` as a webhook URL and subscribe to `checkout_session.payment.paid`. The URL must be publicly reachable over HTTPS. Run `php artisan migrate` to add the checkout-session fields before switching gateways. Customer orders are only marked paid after a valid signed webhook confirms the matching QR Ph session and amount; returning from the checkout page alone does not confirm payment.

## Local database setup

The application uses SQLite by default. Copy `.env.example` to `.env`, generate an application key, then create and seed the local database:

```powershell
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate --seed
```

The database is stored at `database/database.sqlite`. Set `DB_CONNECTION=mysql` and configure the `DB_*` values in `.env` to use MySQL instead.

Role permissions only grant or deny access to routes and actions that explicitly check them. Adding a permission does not generate a page, controller, or navigation link; those need to be implemented separately.

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

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
