# Finance Trading Assessment

A simplified asset (BTC & ETH) trading app built with Laravel and Vue.

## Tech Stack
- Laravel 12
- Vue 3 (Composition API)
- MySQL
- Pusher (real-time events)
- Tailwind CSS

## Features
- USD balance and asset wallet management
- Buy/Sell limit orders with balance locking
- Full-match order matching engine
- 1.5% commission applied on trades
- Real-time updates via private Pusher channels
- Orderbook and user order history

## Setup
```bash
git clone <repo-link>
cd finance
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run dev
php artisan serve
