# SqlBake

Welcome to **SqlBake**, a powerful database management tool designed to streamline SQL scripting tasks, manage database schema changes, and automate routine database operations.

This was developed in 2012 with php 5.5/7.1.
New 2024 release, updated to be used with php 8.1 and 8.2 with the newly added ability to run database migrations for continuous integration best practices
app and codebase agnostic. The only requirements? Read below.


## Overview

**SqlBake** is a versatile utility designed to simplify database management tasks in development, testing, and production environments. With SqlBake, you can:

- Generate SQL scripts for database schema creation, alteration, and patching.
- Execute SQL scripts to apply changes to your database schema.
- Load SQL scripts for database updates and migrations.
- Automate regular backups and maintenance tasks.

## Installation

To use **SqlBake**, ensure you have PHP and MySQL installed on your system:

### Ubuntu/Debian:

```bash
sudo apt update
sudo apt install php mysql-server
```

### Fedora:

```bash
sudo dnf install php mysql-server
```

## Usage

To get started with **SqlBake**, follow these steps:

1. Clone the repository:

   ```bash
   git clone https://github.com/davestj/SqlBake.git
   ```

2. Navigate to the project directory:

   ```bash
   cd SqlBake
   ```

3. Execute **SqlBake** commands using the CLI interface:

   ```bash
   ./sqlbake.php [command] [options]
   ```

   For example, to generate SQL scripts for database tables:

   ```bash
   ./sqlbake.php generate:tables
   ```

### Command Line Options

Use the following command line arguments to execute various operations with SqlBake:

```bash
./sqlbake.php --proc=list,save,clean,load           # Stored procedures operations
./sqlbake.php --table=list,save,clean,load,show     # Table operations
./sqlbake.php --run=test,statuscheck,env            # Run diagnostics
./sqlbake.php --deploy=stage,production,dev         # Run deployment steps
./sqlbake.php --sync=[fromdb,todb]                  # Run database sync
```

These options enable you to perform different database management tasks with SqlBake, including managing stored procedures, tables, running diagnostics, deploying changes, and synchronizing databases.

## Docker Support

**SqlBake** also provides Docker support for easy deployment and management in containerized environments. To run SqlBake using Docker, follow these steps:

1. Ensure you have Docker installed on your system.

2. Clone the repository:

   ```bash
   git clone https://github.com/davestj/SqlBake.git
   ```

3. Navigate to the project directory:

   ```bash
   cd SqlBake
   ```

4. Build the Docker image:

   ```bash
   docker build -t sqlbake .
   ```

5. Run the Docker container:

   ```bash
   docker run -it sqlbake bash
   ```

   This will start a bash shell within the container, allowing you to execute SqlBake commands as usual.

### Docker Compose

Alternatively, you can use Docker Compose to manage SqlBake and its dependencies. Here's how to set it up:

1. Ensure you have Docker Compose installed on your system.

2. Run Docker Compose to build and start the containers:

   ```bash
   docker-compose up -d
   ```

3Access the SqlBake container:

   ```bash
   docker-compose exec sqlbake bash
   ```

   Now you can execute SqlBake commands within the container environment.

## License

**SqlBake** is licensed under the GNU General Public License v3.0 (GPL-3.0).

## Credits

**SqlBake** is developed and maintained by David St John.

For bug reports, help testing and project colab, feature requests, or contributions, please visit the [SqlBake GitHub repository](https://github.com/davestj/SqlBake).
