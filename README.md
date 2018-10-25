# dumpia
## What is this?
This is a PHP script that can download all posts in a fanclub on fantia.jp that you are authorized to access.
It does not allow you to circumvent any paywalls.

## Dependencies
I developed it with PHP 7.2, but it should work with later PHP 5.x versions aswell.
It needs the cURL extension, that should be it.

## Usage
<code>php fantia.php --fanclub <id> --key <key> --output <path> [--verbose]</code>

### Key
You will need to supply a key as an argument.
The key is the value of the _session_id cookie in your browser when you are logged into the site.
You can find it using various methods, likely most accessible to you is your browser's developer console.

### Fanclub
The fanclub parameter takes the fanclub ID you can find in the URL when viewing the fanclub gallery.

## Installation
[![asciicast](https://asciinema.org/a/iv4sioCo0DSEc773H8O8Od0kG.png)](https://asciinema.org/a/iv4sioCo0DSEc773H8O8Od0kG)
