# Flash-Sale Checkout API

**Tech Stack:** Laravel 12, MySQL (InnoDB), Laravel Cache (any driver)  
**Purpose:** Handle flash-sale product checkout safely under high concurrency.  

---

## 1. Assumptions & Invariants

- **Single product** seeded with finite stock.  
- **Holds:**  
  - Temporary (~2 minutes).  
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

## 2. How to Run Locally

1. Clone the repository:  
```bash
git clone <repo-url>
cd flash-sale


Install dependencies:

composer install


Configure .env file:

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=flash_sale
DB_USERNAME=root
DB_PASSWORD=
CACHE_DRIVER=file


Run migrations & seeders:

php artisan migrate --seed


Start Laravel server:

php artisan serve


Run tests (parallel-safe):

php artisan test --parallel

3. API Endpoints
Endpoint	Method	Description
/api/products/{id}	GET	Get product info + accurate available stock
/api/holds	POST	Create temporary hold {product_id, qty}
/api/orders	POST	Create order from valid hold {hold_id}
/api/payments/webhook	POST	Idempotent payment update, out-of-order safe
4. Automated Tests

Parallel Hold Requests

Simulate multiple concurrent holds at stock boundary.

Assert only one hold succeeds, no oversell.

Hold Expiry Returns Stock

Create hold, decrement stock.

Fast-forward time and run cleanup.

Assert stock restored and hold deleted.

Webhook Idempotency

Same webhook key dispatched multiple times.

Assert order updated once, stock not double-changed.

Webhook Before Order Creation

Webhook arrives before order exists.

Returns 202 initially, then 200 after order creation.

Asserts final order and payment state correct.

5. Logs & Metrics

Logs: Laravel default logs in storage/logs/laravel.log.

Metrics / Debug:

Database records (orders, holds, payment_webhooks) show system behavior.

Parallel hold attempts and webhook handling can be traced via tests.