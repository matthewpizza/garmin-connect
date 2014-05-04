# Garmin Connect

This is a PHP wrapper for _some_ of the Garmin Connect API. The API is fairly undocumented and is discouraged. This project was created to assure all Garmin Connect data was backed up outside of the service.

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

The Export class will download GPX and TCX files for all activities found. It can be run multiple times and will only download the activities it does not have.

```php
$export = new Garmin\API\Export($username, $password, $output_path);
```

## Requirements

* PHP 5.4 (probably)
* cURL
* Composer