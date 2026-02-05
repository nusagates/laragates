# Laragates

A comprehensive WhatsApp Business API customer service and engagement platform built with Laravel 12, Inertia.js, and Vue 3.

## Features

### Customer Service Management
- **Multi-Channel Chat System** - Manage customer conversations via WhatsApp Business API
- **Smart Session Management** - Auto-assign, transfer, and track chat sessions with agents
- **Ticket System** - Convert chats to tickets with full lifecycle tracking
- **SLA Management** - Automated SLA tracking with breach detection and notifications
- **Chat Priorities** - Prioritize urgent conversations for faster response times
- **AI-Powered Summaries** - Generate chat summaries using AI for quick context

### Broadcast & Marketing
- **Broadcast Campaigns** - Send bulk WhatsApp messages using approved templates
- **Template Management** - Create, approve, and manage WhatsApp message templates
- **Workflow Approvals** - Built-in approval workflow for broadcasts and templates
- **Campaign Analytics** - Track sent, failed, and delivery statistics
- **Audience Targeting** - Upload CSV contacts or target all customers

### Team Management
- **Multi-Agent Support** - Manage multiple agents with role-based access (Agent, Admin)
- **Online/Offline Status** - Real-time agent availability tracking
- **Skills & Assignment** - Assign conversations based on agent skills
- **Session Limits** - Configure maximum concurrent sessions per agent
- **Audit Logging** - Complete IAM audit trail for security and compliance

### Advanced Features
- **AI Integration** - AI-powered responses with request logging and governance
- **Bot Menu System** - Interactive WhatsApp menus for customer self-service
- **Subscription Management** - Multi-tenant subscription plans with usage tracking
- **Real-time Updates** - Pusher integration for live updates
- **Media Support** - Handle images, documents, and other media types
- **Customer Profiles** - Comprehensive customer data with contact information

## Tech Stack

### Backend
- **Laravel 12** - PHP framework with modern application structure
- **PHP 8.3** - Latest PHP features and performance
- **Inertia.js v2** - Modern monolith architecture with SPA experience
- **Laravel Sanctum** - API authentication
- **Pusher** - Real-time broadcasting
- **MySQL/MariaDB** - Database

### Frontend
- **Vue 3** - Progressive JavaScript framework
- **Vuetify 3** - Material Design component framework
- **Tailwind CSS 4** - Utility-first CSS framework
- **ApexCharts** - Interactive data visualization
- **Ziggy** - Laravel route helpers for JavaScript

### Development & Testing
- **Pest** - Elegant PHP testing framework
- **Laravel Pint** - Opinionated code formatter
- **Laravel Sail** - Docker development environment
- **Vite** - Fast frontend build tool
- **Laravel Boost** - Enhanced MCP development tools

## Getting Started

### Requirements
- PHP 8.3+
- Composer
- Node.js 18+ & npm
- MySQL 8.0+ or MariaDB 10.3+
- WhatsApp Business API credentials

### Installation

1. **Clone the repository**
```bash
git clone <repository-url> laragates
cd laragates
```

2. **Install PHP dependencies**
```bash
composer install
```

3. **Install JavaScript dependencies**
```bash
npm install
```

4. **Environment setup**
```bash
cp .env.example .env
php artisan key:generate
```

5. **Configure database**
Edit `.env` and set your database credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laragates
DB_USERNAME=root
DB_PASSWORD=
```

6. **Configure WhatsApp Business API**
Add your WABA credentials to `.env`:
```env
WABA_API_URL=your_waba_api_url
WABA_TOKEN=your_waba_token
WABA_PHONE_NUMBER_ID=your_phone_number_id
```

7. **Configure Pusher (optional for real-time features)**
```env
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=your_cluster
```

8. **Run migrations**
```bash
php artisan migrate
```

9. **Seed database (optional)**
```bash
php artisan db:seed
```

### Development

Run the development servers:

```bash
# Backend & Frontend together
composer run dev

# Or separately:
npm run dev
php artisan serve
```

Access the application at `http://localhost:8000`

### Building for Production

```bash
npm run build
```

## Testing

Run the test suite:

```bash
# All tests
php artisan test

# Specific test file
php artisan test tests/Feature/ExampleTest.php

# Filter by test name
php artisan test --filter=testName
```

## Code Style

Format code using Laravel Pint:

```bash
vendor/bin/pint
```

## Project Structure

```
app/
├── Console/Commands/      # Artisan commands
├── Events/               # Event classes
├── Exceptions/           # Custom exceptions
├── Helpers/              # Helper classes (WabaHelper)
├── Http/
│   ├── Controllers/      # Application controllers
│   ├── Middleware/       # Custom middleware
│   └── Requests/         # Form request validation
├── Jobs/                 # Queue jobs (broadcasts, messages)
├── Models/               # Eloquent models
├── Notifications/        # Notification classes
├── Policies/            # Authorization policies
├── Providers/           # Service providers
└── Services/            # Business logic services

resources/
├── js/                  # Vue components & Inertia pages
├── css/                 # Stylesheets
└── views/               # Blade templates

routes/
├── web.php              # Web routes
├── api.php              # API routes
├── auth.php             # Authentication routes
├── channels.php         # Broadcasting channels
└── console.php          # Console commands

tests/
├── Feature/             # Feature tests
└── Unit/                # Unit tests
```

## Key Models

- **ChatSession** - Customer conversation sessions
- **ChatMessage** - Individual messages in conversations
- **Customer** - Customer profiles and contact info
- **Agent/User** - System users with roles
- **Ticket** - Support tickets linked to chat sessions
- **BroadcastCampaign** - Bulk message campaigns
- **WhatsappTemplate** - Message templates for broadcasts
- **Subscription** - Tenant subscription management
- **AiRequestLog** - AI usage tracking and governance

## Contributing

1. Create tests for new features
2. Follow existing code conventions
3. Run `vendor/bin/pint` before committing
4. Ensure all tests pass with `php artisan test`

## License

This project is proprietary software.

## Support

For support and questions, please contact the development team.
