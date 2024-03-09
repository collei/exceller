**excel-reader** is a wrapper for the PHPOffice/PHPSpreadsheet package.

Read excel files with ease.

### Requirements
*  PHP 7.2 or newer
*  PHPOffice/PHPSpreadsheet 1.17.0 or newer

### Current Features
*  Reads excel sheets into arrays;
*  Imports excel sheets with user-defined importer classes;
*  Multiple sheet imports with support for skip missing sheets, conditional importing, grouped rows;
*  Localized event handling on importers themselves;
*  Shorthand helpers for importing;

#### To be added
*  ...??

### Testing
1. Unpack the `test.zip` file and navigate to the `test/integrated` folder;
2. Run `serve-windows.cmd` (for Windows) or `serve-linux.sh` (Linux distros);
3. Go `http://localhost:8080` in your browser;
4. Have fun!

Tests are organized in subfolders `/01/`, `/02/` and so on.
You'll find the .xlsx files in the `/files/` folder.
