# WordPress Development Environment Commands
# PowerShell script for managing your WordPress development environment

param(
    [Parameter(Position=0)]
    [string]$Command
)

function Show-Help {
    Write-Host "WordPress Development Environment Commands" -ForegroundColor Green
    Write-Host "==========================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "Usage: .\dev-commands.ps1 <command>" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Available commands:" -ForegroundColor Cyan
    Write-Host "  start     - Start all services"
    Write-Host "  stop      - Stop all services"
    Write-Host "  restart   - Restart all services"
    Write-Host "  status    - Show service status"
    Write-Host "  logs      - Show WordPress logs"
    Write-Host "  db-logs   - Show database logs"
    Write-Host "  shell     - Access WordPress container shell"
    Write-Host "  db-shell  - Access database shell"
    Write-Host "  clean     - Stop and remove all containers and volumes"
    Write-Host "  urls      - Show access URLs"
    Write-Host "  help      - Show this help message"
    Write-Host ""
    Write-Host "Access URLs:" -ForegroundColor Magenta
    Write-Host "  WordPress: http://localhost:8090"
    Write-Host "  phpMyAdmin: http://localhost:8091"
    Write-Host ""
}

function Start-Services {
    Write-Host "Starting WordPress development environment..." -ForegroundColor Green
    docker-compose up -d
    Write-Host "Services started! Access WordPress at http://localhost:8090" -ForegroundColor Green
}

function Stop-Services {
    Write-Host "Stopping WordPress development environment..." -ForegroundColor Yellow
    docker-compose down
    Write-Host "Services stopped." -ForegroundColor Yellow
}

function Restart-Services {
    Write-Host "Restarting WordPress development environment..." -ForegroundColor Yellow
    docker-compose restart
    Write-Host "Services restarted!" -ForegroundColor Green
}

function Show-Status {
    Write-Host "Service Status:" -ForegroundColor Cyan
    docker-compose ps
}

function Show-Logs {
    Write-Host "WordPress Logs (last 50 lines):" -ForegroundColor Cyan
    docker-compose logs wordpress --tail=50
}

function Show-DbLogs {
    Write-Host "Database Logs (last 50 lines):" -ForegroundColor Cyan
    docker-compose logs db --tail=50
}

function Enter-Shell {
    Write-Host "Entering WordPress container shell..." -ForegroundColor Cyan
    docker-compose exec wordpress bash
}

function Enter-DbShell {
    Write-Host "Entering database shell..." -ForegroundColor Cyan
    docker-compose exec db mysql -u wordpress -pwordpress wordpress
}

function Clean-Environment {
    Write-Host "WARNING: This will remove all containers and data!" -ForegroundColor Red
    $confirm = Read-Host "Are you sure? (y/N)"
    if ($confirm -eq 'y' -or $confirm -eq 'Y') {
        docker-compose down -v
        Write-Host "Environment cleaned." -ForegroundColor Yellow
    } else {
        Write-Host "Operation cancelled." -ForegroundColor Green
    }
}

function Show-Urls {
    Write-Host "Access URLs:" -ForegroundColor Magenta
    Write-Host "  WordPress Site: http://localhost:8090" -ForegroundColor White
    Write-Host "  WordPress Admin: http://localhost:8090/wp-admin" -ForegroundColor White
    Write-Host "  phpMyAdmin: http://localhost:8091" -ForegroundColor White
    Write-Host ""
    Write-Host "Default Credentials:" -ForegroundColor Cyan
    Write-Host "  Database: wordpress / wordpress" -ForegroundColor White
    Write-Host "  WordPress Admin: Set up during installation" -ForegroundColor White
}

# Main command dispatcher
switch ($Command.ToLower()) {
    "start" { Start-Services }
    "stop" { Stop-Services }
    "restart" { Restart-Services }
    "status" { Show-Status }
    "logs" { Show-Logs }
    "db-logs" { Show-DbLogs }
    "shell" { Enter-Shell }
    "db-shell" { Enter-DbShell }
    "clean" { Clean-Environment }
    "urls" { Show-Urls }
    "help" { Show-Help }
    default { 
        if ($Command) {
            Write-Host "Unknown command: $Command" -ForegroundColor Red
            Write-Host ""
        }
        Show-Help 
    }
}
