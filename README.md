# 📦 Order & Payment API

## Features

-   **Order Management**: Create, update, delete, and view orders.
-   **Payment Processing**: Multi-gateway support (Credit Card, PayPal, Stripe).
-   **Role-Based Access**: Admin and Customer roles.
-   **JWT Authentication**: Secure API access.
-   **SOLID Architecture**: Repository and Service patterns for clean, testable code.
-   **Repository Pattern**: Data access abstraction
-   **Service Layer**: Business logic encapsulation
-   **Manager/Driver Pattern**: Payment gateway extensibility [Facade, Factory, Strategy, Interface].
-   **Pipeline Pattern**: Order filtering
-   **Strategy Pattern**: Payment method selection
-   **Service Provider**: Dynamic configuration loading
-   **Filtering Pipeline**: Order Filtering (Pipelined)

### Workflow

1.  **Product Management** (Admin/Operations)

    -   Admin creates products via `/api/products`
    -   Products are available for all users to view via `/api/products`

2.  **Order Creation** (Customer)

    -   Customer creates order with `/api/orders`
    -   Order status: **`pending`** (initial state)
    -   Can reference products by `product_id` or manually specify items

3.  **Order Confirmation** (Admin)

    -   Admin reviews and updates order status via `/api/orders/{id}/status`
    -   Status transitions:
        -   `pending` → `confirmed` (enables payment)
        -   `pending` → `cancelled` (blocks payment)

4.  **Payment Processing** (Customer)

    -   Customer lists available payment gateways via `/api/payment-gateways`
    -   Only gateways with configured credentials appear
    -   Default gateway is pre-selected (marked with `is_default: true`)
    -   Customer pays confirmed order via `/api/orders/{id}/pay`
    -   **Multiple partial payments allowed** until total equals order amount
    -   **Overpayment prevention**: Validation blocks payments exceeding order total

5.  **Payment Restrictions**
    -   **Confirmed orders**: Can be paid
    -   **Pending orders**: Cannot be paid (must be confirmed first)
    -   **Cancelled orders**: Cannot be paid
    -   **Fully paid orders**: Additional payments blocked with validation message

### Payment Gateway Configuration

-   Gateways configured via database settings (`payment_gateways` key) and the value that holds all payment gatways configs.
-   `SettingServiceProvider` dynamically loads config at boot
-   Only gateways with credentials appear in customer gateway list
-   Supports: Credit Card, PayPal, Stripe (extensible)
-   **Middleware Validation**: `ValidatePaymentMethod` middleware validates payment methods before processing

## Setup

1. **Clone & Install**

    ```bash
    git clone <repo>
    cd project
    composer install
    cp .env.example .env
    php artisan key:generate
    php artisan jwt:secret
    touch database/database.sqlite
    php artisan migrate:fresh --seed
    php artisan serve
    ```

    This creates:

    - **Admin**: `admin@tocaan.com` / `password`
    - **Customer**: `customer@tocaan.com` / `password`
    - Dummy orders and payments.

## Adding a New Gateway

- To add a new payment gateway to the system follow these steps for example ApplePay:

1.  **Create the Driver Class**: Create a new class `ApplePayDriver.php` in `app/PaymentGateways/Drivers/` that implements `App\Contracts\PaymentGatewayInterface`. Implement the `process` method.
2.  **Add the Driver creation function**: Add a driver creation method `createApplePayDriver` in `app/PaymentGateways/PaymentManager.php`:
3.  **Add to Enum**: Add the new case to `app/Enums/PaymentMethod.php`:
4.  **Configure Credentials (Runtime)**: Add the credentials to the `payment_gateways` setting in the database (or via the Admin Settings API). This is stored as JSON.
5.  The system will now dynamically load "Apple Pay", display it in the list of available gateways for customers, and process payments using your new driver, without writing any additional logic code.

## Postman Collection => Tocaan_Project_Postman_Collection.json