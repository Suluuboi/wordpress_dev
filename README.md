# WordPress Plugin Development Environment

This is a Docker-based WordPress development environment for creating and testing WordPress plugins.

## Quick Start

1. **Start the environment:**

   ```bash
   docker-compose up -d
   ```

2. **Access your sites:**

   - WordPress: http://localhost:8090
   - WordPress Admin: http://localhost:8090/wp-admin
   - phpMyAdmin: http://localhost:8091

3. **Default credentials:**
   - WordPress Admin: Set up during first visit
   - Database: `wordpress` / `wordpress`

## Development Workflow

### Plugin Development

- All plugins go in the `plugin/` directory
- Changes are immediately reflected in WordPress (no need to restart containers)
- Sample plugin included: `my-custom-plugin`

### Database Management

- Use phpMyAdmin at http://localhost:8091
- Or connect directly to MariaDB on localhost:3306

## Services

- **WordPress**: Official WordPress image with PHP and Apache
- **MySQL**: Database server with persistent storage
- **phpMyAdmin**: Web-based database administration

## Useful Commands

```bash
# Start services
docker-compose up -d

# Stop services
docker-compose down

# View logs
docker-compose logs wordpress
docker-compose logs mariadb

# Restart WordPress only
docker-compose restart wordpress

# Access WordPress container shell
docker-compose exec wordpress bash

# Use the PowerShell helper script
.\dev-commands.ps1 start    # Start services
.\dev-commands.ps1 status   # Check status
.\dev-commands.ps1 help     # Show all commands
```

## Plugin Structure

```
plugin/
├── my-custom-plugin/
│   ├── my-custom-plugin.php    # Main plugin file
│   ├── readme.txt              # Plugin documentation
│   └── assets/
│       ├── css/
│       │   └── style.css
│       └── js/
│           └── script.js
```

## Tips for Plugin Development

1. **Adding new plugins**: Create a new folder in `plugin/` and add a volume mount in docker-compose.yml:

   ```yaml
   - ./plugin/your-new-plugin:/var/www/html/wp-content/plugins/your-new-plugin
   ```

2. **Enable WordPress debugging** by adding to wp-config.php:

   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```

3. **Check logs** in the WordPress container:

   ```bash
   docker-compose exec wordpress tail -f /opt/bitnami/wordpress/wp-content/debug.log
   ```

4. **Use WordPress coding standards** and follow best practices

5. **Test thoroughly** before deploying to production

## Troubleshooting

- If WordPress doesn't start, check that MariaDB is healthy: `docker-compose ps`
- For permission issues, ensure the plugin directory is writable
- Clear browser cache if changes don't appear immediately
