<?php
/**
 * Database initialization script for Pearl Edu Fund Donor System
 * Run this file once to set up the database with default data
 */

echo "========================================\n";
echo "Pearl Edu Fund - Database Setup\n";
echo "========================================\n\n";

try {
    // Initialize database
    require 'php/init-db.php';
    
    echo "\n========================================\n";
    echo "✓ Setup completed successfully!\n";
    echo "========================================\n";
    echo "\nYou can now:\n";
    echo "1. Visit: index.html to see the homepage\n";
    echo "2. Click 'Login' button in navigation\n";
    echo "3. Use credentials: admin / admin123\n";
    echo "\nOr access directly: login.html\n";
    
} catch (Exception $e) {
    echo "\n✗ Error during setup:\n";
    echo $e->getMessage() . "\n";
    exit(1);
}
?>
