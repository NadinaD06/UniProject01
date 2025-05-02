# UniSocial Site

A social media platform for university students.

## Directory Structure

```
UniProject01/
├── app/                    # Application core
│   ├── config/            # Configuration files
│   │   └── config.php   # Database configuration
│   ├── controllers/       # Controller classes
│   ├── core/             # Core framework classes
│   ├── migrations/       # Database migrations
│   ├── models/           # Model classes
│   ├── routes/           # Route definitions
│   └── views/            # View templates
├── public/               # Publicly accessible files
│   ├── assets/          # Compiled assets
│   │   ├── css/        # CSS files
│   │   ├── js/         # JavaScript files
│   │   └── images/     # Image files
│   └── uploads/         # User uploaded files
├── tests/               # Test files
│   ├── unit/           # Unit tests
│   └── integration/    # Integration tests
├── vendor/             # Composer dependencies
├── .htaccess          # Apache configuration
├── composer.json      # Composer configuration
├── index.php          # Application entry point
└── README.md          # Project documentation
```

## Setup Instructions

1. Clone the repository:
```bash
git clone [repository-url]
cd UniProject01
```

2. Install dependencies:
```bash
composer install
```

3. Configure the database:
- Copy `app/config/config.example.php` to `app/config/config.php`
- Update the database credentials in `config.php`

4. Run database migrations:
```bash
php app/migrations/migrate.php
```

5. Set up the web server:
- Point the document root to the `public` directory
- Ensure the `uploads` directory is writable
- Configure URL rewriting (mod_rewrite for Apache)

6. Start the development server:
```bash
php -S localhost:8000 -t public
```

## Features

- User registration and authentication
- Post creation with image upload
- Like and comment functionality
- Real-time messaging
- User profiles
- Admin dashboard
- Location-based features
- Real-time notifications

## Development

- Follow PSR-4 autoloading standards
- Use PHP 7.4 or higher
- Follow the MVC pattern
- Write tests for new features
- Use prepared statements for database queries
- Sanitize all user input
- Implement proper error handling

## Testing

Run the test suite:
```bash
./vendor/bin/phpunit
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This project is licensed under the MIT License.