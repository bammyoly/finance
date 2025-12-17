# Asset Trading App

A real-time trading app built with **Laravel** and **Vue 3**, demonstrating
financial data integrity, concurrency safety, and real-time order matching.

This project implements a minimal crypto-style exchange for **BTC / ETH vs USD** with
locked balances, full-match order execution, and live updates.

---

## Tech Stack

- **Backend**: Laravel 12
- **Frontend**: Vue 3 (Composition API) via Inertia
- **Database**: MySQL
- **Real-time**: Pusher (Laravel Broadcasting)
- **Styling**: Tailwind CSS
- **Auth**: Laravel session-based authentication

---

## Core Features

- User USD wallet and asset balances
- Buy / Sell limit orders
- Balance and asset locking for open orders
- **Full-match-only order matching**
- **First valid counter-order matching rule**
- **1.5% commission applied on matched trades**
- Order cancellation with locked fund release
- Real-time updates via Pusher private channels
- Orderbook, wallet, and user order history UI

---

## API Endpoints (authenticated)


| Method | Endpoint | Description |
|------|--------|-------------|
| GET | `/api/profile` | Returns authenticated user's USD balance and assets |
| GET | `/api/orders?symbol=BTC` | Returns open buy & sell orders for orderbook |
| POST | `/api/orders` | Create a buy or sell limit order |
| POST | `/api/orders/{id}/cancel` | Cancel an open order and release locked funds |
| GET | `/api/my-orders` | Returns user’s open / filled / cancelled orders |

---

## Matching Rules

- **Full match only** (no partial fills)
- New **BUY** matches first **SELL** where `sell.price ≤ buy.price`
- New **SELL** matches first **BUY** where `buy.price ≥ sell.price`
- Orders are matched FIFO by creation time
- Both orders transition to `FILLED` on successful match

---

## Commission

- **1.5% of matched USD value**
- Applied consistently during settlement
- Example:
  - `0.01 BTC @ 95,000 USD`
  - Volume = `950 USD`
  - Fee = `14.25 USD`

---

## Real-Time Events

- Event: `OrderMatched`
- Channel: `private-user.{id}`
- Triggered on successful order match
- Frontend updates:
  - Wallet balances
  - Orderbook
  - Order history
  - Trade notification

---

## Local Setup

### Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+
- MySQL
- Pusher

### Installation


```bash
git clone https://github.com/bammyoly/finance.git
cd finance

composer install
npm install

cp .env.example .env
php artisan key:generate
php artisan migrate

npm run dev
php artisan serve
...
php artisan queue:work


