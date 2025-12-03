# MySQL Auto Backup Script

## Description
This script automates the process of backing up your MySQL database and sending the backup to specified Telegram users. It's intended to be used as a cron job, allowing for scheduled backups of your database at specified intervals.

**Features:**
- Automatically exports and backs up your MySQL database.
- Compresses the backup into a zip file.
- Sends the backup file to selected Telegram users via Telegram bot.
- Simple to configure by editing the parameters in the script.

## Requirements
- PHP 7 or higher
- MySQL database
- A Telegram Bot API key (you can get it by creating a bot with @BotFather on Telegram)
- Cron job setup for scheduling backups

## Installation

1. Clone or download the repository to your server.

   `git clone https://github.com/realSina/mysql-auto-backup.git`

2. Open the script (`index.php`) and replace the following placeholders:

   - `TELEGRAM_API_KEY`: Your Telegram Bot API Key.
   - `ADMINS`: A list of Telegram user IDs to send the backup link to.
   - `SECURITY`: A secret password to authorize backups.
   - `SQL`: Database connection details (host, username, password, database).
   - `Directory`: Your script directory on line 24.

3. Place the script in a web-accessible directory, but ensure it is secure (e.g., password-protected).

4. Set up a **cron job** to run the script at regular intervals. For example, to run it every day at midnight:

   `crontab -e`

   Add the following line to your crontab:

   `0 0 * * * php /path/to/your/script/index.php?password=YOUR_SECRET_PASSWORD`

## Usage

Once configured, the script can be triggered by visiting the URL of the script with the correct password parameter. For example:

`http://yourserver.com/backup/index.php?password=YOUR_SECRET_PASSWORD`

When triggered, the script will:
1. Create a backup of your MySQL database.
2. Zip the backup file.
3. Send a Telegram message as a document type to the specified admin users.

## Notes
- Make sure to adjust your server permissions to allow file creation and zipping.
- This script assumes your database tables do not have foreign key constraints or complex relations. For complex databases, you may need to modify the script to handle those scenarios.
- It's important to secure the backup script with a password, and also consider restricting the access to it via `.htaccess` or similar security measures.

## License
This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
