# dumpia
## What is this?
This is a PHP script that can download all posts in a fanclub on fantia.jp that you are authorized to access.
It does not allow you to circumvent any paywalls.

## Dependencies
I developed it with PHP 7.2, but it should work with later PHP 5.x versions aswell.
It needs the cURL extension, that should be it.

## Easy Usage
Just specify the output directory in dumpia.sh and type ./dumpia.sh.  
After entering the IDs, a directory for each ID will be created automatically, and the files will be downloaded into that directory.  
If you want to use ```[--verbose] [--downloadExisting] [--exitOnFreePlan],``` please add it in line 5

## Usage
```php dumpia.php --key <key> --output <path> [--fanclub <id>] [--verbose] [--downloadExisting] [--exitOnFreePlan]```

### Key
You will need to supply a key as an argument.
The key is the value of the _session_id cookie in your browser when you are logged into the site.
You can find it using various methods, likely most accessible to you is your browser's developer console.

### Fanclub
The fanclub parameter takes the fanclub ID you can find in the URL when viewing the fanclub gallery.

## Installation
[![asciicast](https://asciinema.org/a/yM1E9Ia4U8mTioqVNG8gIEvB4.svg)](https://asciinema.org/a/yM1E9Ia4U8mTioqVNG8gIEvB4)
