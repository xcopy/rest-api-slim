CREATE DATABASE IF NOT EXISTS rest_api_example         CHARACTER SET utf8mb4;
CREATE DATABASE IF NOT EXISTS rest_api_example_local   CHARACTER SET utf8mb4;
CREATE DATABASE IF NOT EXISTS rest_api_example_testing CHARACTER SET utf8mb4;

GRANT ALL PRIVILEGES ON rest_api_example.*         TO 'app'@'%';
GRANT ALL PRIVILEGES ON rest_api_example_local.*   TO 'app'@'%';
GRANT ALL PRIVILEGES ON rest_api_example_testing.* TO 'app'@'%';
FLUSH PRIVILEGES;