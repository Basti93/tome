-- Initialize Matomo database in shared MySQL
CREATE DATABASE IF NOT EXISTS matomo;
GRANT ALL PRIVILEGES ON matomo.* TO 'tome'@'%';
FLUSH PRIVILEGES;
