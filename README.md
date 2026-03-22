# FlowCast

AI-powered podcast production platform — record, transcribe, enhance, and publish podcasts with intelligent automation.

## Tech Stack

- **Backend:** Laravel 13 (PHP 8.3+), PostgreSQL
- **Frontend:** Vue 3, TypeScript, Tailwind CSS, Vite
- **AI:** OpenAI API integration
- **Storage:** S3-compatible object storage
- **Auth:** Laravel Sanctum (API token authentication)

## Setup

### Prerequisites

- PHP 8.3+
- Composer
- Node.js 18+
- PostgreSQL 15+

### Installation

```bash
# Install PHP dependencies
composer install

# Install frontend dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Create PostgreSQL database
createdb flowcast

# Run migrations
php artisan migrate

# Start development server
php artisan serve

# In a separate terminal, start Vite
npm run dev
```

### Environment Configuration

Copy `.env.example` to `.env` and configure:

- **Database:** PostgreSQL connection (`DB_*` variables)
- **Storage:** S3-compatible storage (`AWS_*` variables)
- **OpenAI:** API key and organization (`OPENAI_*` variables)

## Architecture

See [docs/architecture.md](docs/architecture.md) for the full architecture document.
