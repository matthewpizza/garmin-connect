# Garmin Connect

This project was created to assure all Garmin Connect data was backed up outside of the service.

:warning: As of Thursday, July 21, 2016, the endpoint for downloading gpx/tcx activities was [“retired”](https://github.com/matthewspencer/runs/commit/c61acde28f3d10d3f0a8ef551eb8356758dbb73c). I plan to update the endpoints in the library soon. 

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
new MatthewSpencer\GarminConnect\Export( $email, $password, $output_path );
```
