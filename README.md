# Zipper

![Icon](screenshots/icon.png)

The independent single file archives extractor and files compressor for web servers. It detects archives and you choose which one to extract. You can also compress files to create archives.

## Supported Formats

- `.zip`
- `.rar`
- `.gz`

## Built with

- HTML
- CSS
- JavaScript
- PHP

### Dependencies

- [Bootstrap](https://getbootstrap.com) `v4.0.0`
- [jQuery](https://jquery.com) `v3.3.1`

## Screenshots

| Extract                             | Compress                              |
| ----------------------------------- | ------------------------------------- |
| ![Extract](screenshots/extract.png) | ![Compress](screenshots/compress.png) |

## Requirements

- PHP `5.3` and newer

## Installation

- Download `zipper.php` and place it in the same directory as your archives.
- Open `https://<your-path>/zipper.php` in your browser.

## Usage

### Extract

1. Select the file you want to extract.
2. Enter the **Extraction Path** or leave it empty for the current directory `(optional)`.
3. Click on **Extract**.

### Compress

1. Enter the **Compression Path** to the archive or leave it empty to the current directory `(optional)`.
2. Click on **Compress**.

## Credits

- Zipper is based on [The Unzipper](https://github.com/ndeet/unzipper).

## License

This software is licensed under the terms of the [MIT](LICENSE.md) license.