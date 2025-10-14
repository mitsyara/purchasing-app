<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    protected string $dbName;
    protected string $host;
    protected string $username;
    protected string $password;
    protected int $port;

    public function __construct()
    {
        $config = config('database.connections.mysql_customs_data');

        $this->dbName   = $config['database'];
        $this->host     = $config['host'];
        $this->port     = $config['port'] ?? 3306;
        $this->username = $config['username'];
        $this->password = $config['password'];

    }

    public function up(): void
    {
        // Tạo database nếu chưa tồn tại, dùng PDO trực tiếp
        $pdo = new \PDO("mysql:host={$this->host};port={$this->port}", $this->username, $this->password);
        $pdo->exec("DROP DATABASE IF EXISTS `{$this->dbName}`");
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$this->dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    public function down(): void
    {
        $pdo = new \PDO("mysql:host={$this->host};port={$this->port}", $this->username, $this->password);
        $pdo->exec("DROP DATABASE IF EXISTS `{$this->dbName}`");
    }
};
