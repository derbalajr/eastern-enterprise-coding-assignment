# Implementation Notes

A Symfony-based REST API for managing country data with synchronization from REST Countries API.

## Setup

```bash
docker-compose up --build -d
```

Visit http://localhost:8084 to access the application.

## Features

### Database & Models
- Country entity with Currency embeddable
- Doctrine migrations for schema management
- Soft delete support

### Data Synchronization
- `CountryService` fetches data from REST Countries API
- `countries:sync` command synchronizes database with API
- Automatically removes obsolete countries and resets modified ones

### API Endpoints
- `GET /api/v1/countries/list` - List all countries (paginated, public)
- `GET /api/v1/countries/{uuid}` - Get single country (public)
- `POST /api/v1/countries` - Create country (requires auth)
- `PATCH /api/v1/countries/{uuid}` - Update country (requires auth)
- `DELETE /api/v1/countries/{uuid}` - Delete country (soft delete, requires auth)

### Security
- HTTP Basic Authentication for POST/PATCH/DELETE
- GET endpoints remain public
- Memory User Provider (admin/admin)

### API Documentation
- Available at http://localhost:8084/api/doc
- OpenAPI/Swagger UI integration
- Postman collection available: `postman_collection.json`

## Usage

### Import Postman Collection
1. Import `postman_collection.json` into Postman
2. Optionally import `postman_environment.json` for environment variables
3. Collection includes all CRUD endpoints with example requests
4. Basic Auth (admin/admin) is pre-configured for POST/PATCH/DELETE endpoints

### Sync Countries
```bash
docker-compose exec php php bin/console countries:sync
```

### Run Tests
```bash
docker-compose exec php php bin/phpunit
```

## Technical Details

- PHP 8.2+
- Symfony 7.x
- Doctrine ORM
- REST Countries API integration
- Unit tests with PHPUnit

