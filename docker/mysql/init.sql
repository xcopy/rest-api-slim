CREATE DATABASE IF NOT EXISTS rest_api_slim         CHARACTER SET utf8mb4;
CREATE DATABASE IF NOT EXISTS rest_api_slim_local   CHARACTER SET utf8mb4;
CREATE DATABASE IF NOT EXISTS rest_api_slim_testing CHARACTER SET utf8mb4;

GRANT ALL PRIVILEGES ON rest_api_slim.*         TO 'app'@'%';
GRANT ALL PRIVILEGES ON rest_api_slim_local.*   TO 'app'@'%';
GRANT ALL PRIVILEGES ON rest_api_slim_testing.* TO 'app'@'%';
FLUSH PRIVILEGES;