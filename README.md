# Flash-Sale Checkout API 🚀

**Tech Stack:** Laravel 12, MySQL (InnoDB), Laravel Cache (any driver)  
**Purpose:** Handle flash-sale product checkout safely under high concurrency. 💳

---

## 1. Assumptions & Invariants 🔍

- **Single product** seeded with finite stock.  
- **Holds:**  
    - Temporary (~2 minutes). ⏳  
    - Reduce available stock immediately.  
    - Expired holds auto-release stock.  
- **Orders:**  
    - Only valid, unexpired holds can create orders.  
    - Each hold can be used once.  
- **Payment Webhook:**  
    - Idempotent: same `idempotency_key` cannot be applied twice.  
    - Safe if received before order creation (returns 202 until order exists).  
- **Concurrency:**  
    - Parallel hold requests must not oversell.  
    - Atomic stock adjustments simulated in tests.  
- **Data integrity:**  
    - Stock never goes negative.  
    - Holds and orders maintain consistency.

---

## 2. How to Run Locally 🛠️

1. Clone the repository:  
```bash
git clone https://github.com/AhmedShabaan10/Flash-Sale.git
cd flash-sale
```

2. Install dependencies:
```bash
composer install
```

3. Configure `.env` file:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=flash_sale
DB_USERNAME=root
DB_PASSWORD=
CACHE_DRIVER=file
```

4. Run migrations & seeders:
```bash
php artisan migrate --seed
```

5. Start Laravel server:
```bash
php artisan serve
```

6. Run tests:
```bash
php artisan test --parallel
```

---

## 3. API Endpoints 📡

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/products/{id}` | GET | Get product info + available stock |
| `/api/holds` | POST | Create temporary hold `{product_id, qty}` |
| `/api/orders` | POST | Create order from valid hold `{hold_id}` |
| `/api/payments/webhook` | POST | Idempotent payment update |

---

## 4. Automated Tests 🧪

- **Parallel Hold Requests:** Simulate concurrent holds at stock boundary; assert only one succeeds.
- **Hold Expiry Returns Stock:** Verify stock restoration after hold expiration.
- **Webhook Idempotency:** Same webhook key dispatched multiple times; assert order updated once.
- **Webhook Before Order Creation:** Webhook arrives before order exists; returns 202 initially, then 200 after creation.

---

## 5. Logs & Metrics 📊

- **Logs:** Laravel default logs in `storage/logs/laravel.log`
- **Database records:** Track orders, holds, and payment_webhooks
- **Parallel hold attempts and webhook handling:** Traced via tests

