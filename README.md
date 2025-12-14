# 📦 Order & Payment API

A robust, localized Laravel 12 API for managing orders and payments with an extensible payment gateway system.

## 🚀 Features

-   **Order Management**: Create, update, delete, and view orders.
-   **Payment Processing**: Multi-gateway support (Credit Card, PayPal, Stripe) using Manager/Driver pattern.
-   **Role-Based Access**: Admin and Customer roles.
-   **JWT Authentication**: Secure API access.
-   **SOLID Architecture**: Repository and Service patterns for clean, testable code.

## 🛠️ Architecture

-   **Patterns**: Repository, Service, Manager (Drivers), Strategy.
-   **Tech Stack**: Laravel 12, PHP 8.2+, MySQL, JWT Auth.
-   **Code Quality**: PSR-12, Types, Strict Mode.

## 🧠 Complete Workflow & Business Rules

### Order-to-Payment Flow

1.  **Product Management** (Admin/Operations)

    -   Admin creates products via `POST /api/products`
    -   Products are available for all users to view via `GET /api/products`

2.  **Order Creation** (Customer)

    -   Customer creates order with `POST /api/orders`
    -   Order status: **`pending`** (initial state)
    -   Can reference products by `product_id` or manually specify items

3.  **Order Confirmation** (Admin)

    -   Admin reviews and updates order status via `PATCH /api/orders/{id}/status`
    -   Status transitions:
        -   `pending` → `confirmed` (enables payment)
        -   `pending` → `cancelled` (blocks payment)

4.  **Payment Processing** (Customer)

    -   Customer lists available payment gateways via `GET /api/payment-gateways`
    -   Only gateways with configured credentials appear
    -   Default gateway is pre-selected (marked with `is_default: true`)
    -   Customer pays confirmed order via `POST /api/orders/{id}/pay`
    -   **Multiple partial payments allowed** until total equals order amount
    -   **Overpayment prevention**: Validation blocks payments exceeding order total

5.  **Payment Restrictions**
    -   ✅ **Confirmed orders**: Can be paid
    -   ❌ **Pending orders**: Cannot be paid (must be confirmed first)
    -   ❌ **Cancelled orders**: Cannot be paid
    -   ❌ **Fully paid orders**: Additional payments blocked with validation message

### Role-Based Access Control

-   **Admin**:
    -   View all orders and payments
    -   Update order status (confirm/cancel)
    -   Manage products and system settings
-   **Customer**:
    -   View only their own orders and payments
    -   Create orders and process payments
    -   List available payment gateways

### Payment Gateway Configuration

-   Gateways configured via database settings (`payment_gateways` key)
-   `SettingServiceProvider` dynamically loads config at boot
-   Only gateways with credentials appear in customer gateway list
-   Supports: Credit Card, PayPal, Stripe (extensible)
-   **Middleware Validation**: `ValidatePaymentMethod` middleware validates payment methods before processing

### Middleware Stack

-   **CheckRole**: Enforces role-based access control (Admin/Customer)
-   **ValidatePaymentMethod**: Validates and configures payment methods for payment routes
-   **JWT Authentication**: Secures all protected endpoints

## ⚙️ Setup

1. **Clone & Install**

    ```bash
    git clone <repo>
    cd tocaan-project
    composer install
    cp .env.example .env
    php artisan key:generate
    php artisan jwt:secret
    ```

2. **Database**
   Configure your database in `.env`.

    ```env
    DB_CONNECTION=sqlite
    # or mysql details...
    ```

3. **Migrate & Seed**

    ```bash
    php artisan migrate:fresh --seed
    ```

    This creates:

    - **Admin**: `admin@tocaan.com` / `password`
    - **Customer**: `customer@tocaan.com` / `password`
    - Dummy orders and payments.

4. **Serve**
    ```bash
    php artisan serve
    ```

## 🔌 API Endpoints

### Authentication

-   `POST /api/auth/login` - Login
-   `POST /api/auth/logout` - Logout
-   `GET /api/auth/profile` - Get user profile
-   `POST /api/auth/refresh` - Refresh token

### Orders

-   `GET /api/orders` - List orders (filtered by role)
-   `POST /api/orders` - Create order
-   `GET /api/orders/{id}` - View order
-   `PUT /api/orders/{id}` - Update order items
-   `PATCH /api/orders/{id}/status` - Update order status (Admin only)
-   `DELETE /api/orders/{id}` - Delete order
-   **Payments for Order**:
    -   `POST /api/orders/{id}/pay` - Process payment for this order
    -   `GET /api/orders/{id}/payments` - View payment history

### Payments (Admin/Audit)

-   `GET /api/payments` - List payments (Admin: all, Customer: own)
-   `GET /api/payments/{id}` - View payment details
-   `GET /api/payments/{id}/verify` - Verify payment status

### Payment Gateways

-   `GET /api/payment-gateways` - List available configured gateways

### Products

-   `GET /api/products` - List all products (Public)
-   `POST /api/products` - Create product (Admin only)
-   `GET /api/products/{id}` - View product (Public)
-   `PUT /api/products/{id}` - Update product (Admin only)
-   `DELETE /api/products/{id}` - Delete product (Admin only)

### Settings

-   `GET /api/settings` - List configuration
-   `PUT /api/settings/{key}` - Update setting (Admin only)

## 🔍 Advanced Features

### Order Filtering (Pipelined)

Orders can be filtered using query parameters. This uses a robust **Pipeline Pattern**.

-   `?status=pending` (or confirmed, cancelled)
-   `?min_total=100` (Orders above this amount)
-   `?date_from=2024-01-01`
-   `?date_to=2024-12-31`
    Example: `/api/orders?status=pending&min_total=500`

## 💳 Extensibility: Adding a New Gateway

To add a new payment gateway (e.g., "Apple Pay") to the system, follow these steps:

1.  **Create the Driver Class**:
    Create a new class `ApplePayDriver.php` in `app/PaymentGateways/Drivers/` that implements `App\Contracts\PaymentGatewayInterface`. Implement the `process` method.

2.  **Register the Driver**:
    In `app/PaymentGateways/PaymentManager.php`, add a driver creation method:

    ```php
    public function createApplePayDriver()
    {
        return new ApplePayDriver($this->config['apple_pay'] ?? []);
    }
    ```

3.  **Update the Enum**:
    Add the new case to `app/Enums/PaymentMethod.php`:

    ```php
    case APPLE_PAY = 'apple_pay';
    ```

4.  **Configure Credentials (Runtime)**:
    Add the credentials to the `payment_gateways` setting in the database (or via the Admin Settings API). This is stored as JSON:
    ```json
    [
        {
            "code": "apple_pay",
            "credentials": { "merchant_id": "..." },
            "is_default": false
        }
    ]
    ```

**Result**: The system will now dynamically load "Apple Pay", display it in the list of available gateways for customers, and process payments using your new driver, without writing any additional logic code.

## 📖 User Stories

### Customer

-   **Browse & Order**: "As a customer, I want to view products and create orders so I can purchase items."
-   **Track Orders**: "As a customer, I want to view my order history and see the status of each order."
-   **Process Payment**: "As a customer, I want to pay for my confirmed orders using my preferred payment method (Credit Card, etc.) securely."
-   **Partial Payments**: "As a customer, I want to be able to make partial payments if needed until the checks are balanced."

### Admin

-   **Order Fulfillment**: "As an admin, I want to view all incoming orders and confirm or cancel them based on inventory."
-   **Product Management**: "As an admin, I want to add, update, and remove products from the catalog."
-   **Dynamic Configuration**: "As an admin, I want to configure payment gateways (API keys, default status) directly from the settings without modifying the code."
-   **Audit**: "As an admin, I want to view a complete log of all payments and transactions for financial auditing."

## 🧪 Testing

Run specific feature tests:

```bash
php artisan test tests/Feature/OrderFlowTest.php
```

Run all tests:

```bash
php artisan test
```

## 📝 Key Implementation Details

-   **Payment Processing**: Synchronous simulation with configurable success rates
-   **Testing Environment**: Payments always succeed in test environment for deterministic tests
-   **Order Status Transitions**: Enforced via `OrderStatus` enum with validation
-   **Overpayment Prevention**: Sums successful + pending payments before accepting new payment
-   **Gateway Configuration**: Dynamic loading from database via `SettingServiceProvider`
-   **Authorization**: Middleware-based role checks + controller-level ownership validation

## 📚 Design Patterns Used

-   **Repository Pattern**: Data access abstraction
-   **Service Layer**: Business logic encapsulation
-   **Manager/Driver Pattern**: Payment gateway extensibility
-   **Pipeline Pattern**: Order filtering
-   **Strategy Pattern**: Payment method selection
-   **Service Provider**: Dynamic configuration loading
