# Social Media Web Site

A social media platform similar to Facebook, built with PHP, MySQL, HTML, CSS, and JavaScript/jQuery.

## Features

### User Features
- User registration and login
- Create wall posts with text, images, and location
- Like and comment on posts
- Send and receive private messages
- Block/unblock users
- Report users

### Admin Features
- Delete users
- View post statistics (weekly/monthly/yearly)
- Manage user reports
- Take action on reported users

## Requirements

- PHP 8.0 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Composer (PHP package manager)
- Node.js and npm (for frontend assets)

## Installation

1. Clone the repository:
```bash
git clone <repository-url>
cd social-media-site
```

2. Install PHP dependencies:
```bash
composer install
```

3. Install frontend dependencies:
```bash
npm install
```

4. Create a MySQL database and import the schema:
```bash
mysql -u your_username -p your_database_name < app/Migrations/create_tables.sql
```

5. Configure the database connection:
   - Copy `app/config/db_config.example.php` to `app/config/db_config.php`
   - Update the database credentials in `db_config.php`

6. Set up the web server:
   - Point your web server's document root to the `public` directory
   - Ensure the `uploads` directory is writable by the web server

7. Build frontend assets:
```bash
npm run build
```

## Testing

### Manual Testing

1. User Registration and Login:
   - Visit the registration page and create a new account
   - Try logging in with the created account
   - Test password validation and error messages

2. Wall Posts:
   - Create a new post with text
   - Upload an image with a post
   - Add location to a post
   - Like and comment on posts
   - Verify post visibility and ordering

3. Messaging:
   - Send a message to another user
   - Check message delivery
   - Verify unread message count
   - Test conversation history

4. User Management:
   - Block a user
   - Report a user
   - Verify blocked user's content is hidden
   - Test unblocking functionality

5. Admin Features:
   - Log in as admin
   - View post statistics
   - Check user reports
   - Delete a user
   - Take action on reported users

### Automated Testing

Run the test suite:
```bash
./vendor/bin/phpunit
```

## Security Considerations

- All passwords are hashed using PHP's password_hash()
- SQL injection prevention using prepared statements
- XSS protection through output escaping
- CSRF protection on forms
- Input validation and sanitization
- Secure file upload handling

## File Structure

```
├── app/
│   ├── Auth/         # Authentication related code
│   ├── Controllers/  # Application controllers
│   ├── Core/         # Core framework components
│   ├── Models/       # Database models
│   ├── Views/        # View templates
│   └── config/       # Configuration files
├── public/           # Publicly accessible files
├── uploads/          # User uploaded files
├── assets/          # Frontend assets
└── tests/           # Test files
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.