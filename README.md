# DevOps ToolSmiths - SqlBake

Welcome to **DevOps ToolSmiths - SqlBake**, a powerful database management tool designed to streamline SQL scripting tasks, manage database schema changes, and automate routine database operations.

This was developed in 2012 with php 5.5/7.1.
New 2024 release, updated to be used with php 8.1 and 8.2 with the newly added ability to run database migrations for continuous integration best practices
app and codebase agnostic. The only requirements? Read below.


## Overview

**SqlBake** is a versatile utility developed by DevOps ToolSmiths to simplify database management tasks in development, testing, and production environments. With SqlBake, you can:

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
   git clone https://github.com/DevOps-ToolSmiths/SqlBake.git
   ```

2. Navigate to the project directory:

   ```bash
   cd SqlBake
   ```

3. Execute **SqlBake** commands using the CLI interface:

   ```bash
   php sqlbake.php [command] [options]
   ```

   For example, to generate SQL scripts for database tables:

   ```bash
   php sqlbake.php generate:tables
   ```

## Load SQL Alter and Patch Scripts

The "Load SQL Alter and Patch Scripts" feature enables seamless database updates and migrations, irrespective of the underlying codebase or application framework. It provides a standardized method to apply SQL scripts for altering schema, applying patches, or performing migrations.

### Usage

To utilize this feature, follow these steps:

1. Organize your SQL scripts into a directory structure based on their purpose or version.
2. Use the following command to load the SQL scripts into the database:

   ```bash
   php sqlbake.php load:scripts path/to/scripts/directory
   ```

### Benefits

- **Framework Agnostic**: This functionality is not tied to any specific application framework, making it compatible with a wide range of systems.
- **Database Compatibility**: Works seamlessly with MySQL, MariaDB, PostgreSQL, and AWS Aurora database systems.
- **Version Control**: Easily manage and track database schema changes using version control systems like Git.
- **Integration**: Can be integrated into Continuous Integration/Continuous Deployment (CI/CD) pipelines or deployment workflows alongside other database management tools.

### Example

Suppose you have a set of SQL scripts stored in a directory named "migrations" within your project repository. To apply these scripts to your database, you can use the following command:

```bash
php sqlbake.php load:scripts /path/to/project/migrations
```

This command will execute all SQL scripts found in the "migrations" directory, applying any schema changes or updates to the connected database.

### Note

Ensure that your SQL scripts are compatible with the target database system and that you have appropriate permissions to execute them.

## Docker Support

**SqlBake** also provides Docker support for easy deployment and management in containerized environments. To run SqlBake using Docker, follow these steps:

1. Ensure you have Docker installed on your system.

2. Clone the repository:

   ```bash
   git clone https://github.com/DevOps-ToolSmiths/SqlBake.git
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

## Docker Compose

Alternatively, you can use Docker Compose to manage SqlBake and its dependencies. Follow these steps:

1. Ensure you have Docker Compose installed on your system.

2. Navigate to the project directory containing the `docker-compose.yml` file.

3. Run the following command to build and start the containers:

   ```bash
   docker-compose up -d
   ```

4. Once the containers are running, you can access SqlBake using:

   ```bash
   docker-compose exec php bash
   ```

   This will start a bash shell within the PHP container, allowing you to execute SqlBake commands as usual.

## License

**SqlBake** is licensed under the GNU General Public License v3.0 (GPL-3.0).

## Credits

**SqlBake** is developed and maintained by David St John at [DevOps ToolSmiths](https://devops-toolsmiths.com/).

For bug reports, feature requests, or contributions, please visit the [SqlBake GitHub repository](https://github.com/davestj/SqlBake).
