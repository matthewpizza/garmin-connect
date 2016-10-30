# Garmin Connect

This project was created to assure all Garmin Connect data was backed up outside of the service.

## Installation

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/matthewspencer/garmin-connect"
        }
    ],
    "require": {
        "matthewspencer/garmin-connect": "dev-master"
    },
    "minimum-stability": "dev"
}
```

```bash
composer install
```

## Usage

### Export

The Export class will download the original activity file for all activities found. It can be run multiple times and will only download the activities it does not have.

```php
new MatthewSpencer\GarminConnect\Export( $email, $password, $output_path );
```
