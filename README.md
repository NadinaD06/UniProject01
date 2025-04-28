# Social Media Site

A basic social media site with an artsy, cartoon theme.

## Features

- User registration and authentication
- Profile management
- Post creation and interaction
- Like and comment functionality
- User following system
- Privacy settings
- Notification preferences

## Requirements

- PHP 7.4 or higher
- PostgreSQL 12 or higher
- Apache with mod_rewrite enabled
- Composer (for dependency management)

## Installation

1. Clone the repository:
```bash
git clone <repository-url>
cd social-media-site
```

2. Create a PostgreSQL database and update the configuration in `config/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
```

3. Set up the database tables:
```bash
php setup_tables.php
```

4. Configure your web server:
   - Point the document root to the project directory
   - Ensure mod_rewrite is enabled
   - Set the correct permissions for the uploads directory

5. Create the uploads directory:
```bash
mkdir -p app/public/uploads/avatars
chmod 777 app/public/uploads/avatars
```

## Testing the Site

1. Access the site through your web browser:
```
http://your-domain.com
```

2. Register a new account:
   - Click "Register" on the login page
   - Fill in the required information
   - Submit the form

3. Test the main features:
   - Create a post
   - Like and comment on posts
   - Update your profile
   - Follow other users
   - Test the settings page

4. Check the following functionality:
   - User authentication (login/logout)
   - Post creation and display
   - Like and comment system
   - Profile viewing and editing
   - Settings management
   - Privacy controls
   - Notification preferences

## Troubleshooting

1. Database Connection Issues:
   - Verify database credentials in config.php
   - Check if PostgreSQL is running
   - Ensure the database exists

2. File Upload Issues:
   - Check directory permissions
   - Verify PHP upload settings
   - Check file size limits

3. URL Rewriting Issues:
   - Ensure mod_rewrite is enabled
   - Check .htaccess file
   - Verify Apache configuration

## Security Considerations

- All user input is sanitized
- Passwords are hashed using PHP's password_hash()
- CSRF protection is implemented
- XSS protection headers are set
- SQL injection prevention using prepared statements

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.