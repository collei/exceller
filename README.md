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
1. Unpack the `test.zip` at the project's base folder;
2. Navigate into the `test` folder.

#### Unit Tests
* Run `test-unit.cmd` (for Windows) or `test-unit.sh` (Linux distros) and see the results.

#### Integrated
* Run `serve-integrated.cmd` (for Windows) or `serve-integrated.sh` (Linux distros), then go `http://localhost:8080` in your browser.

Such tests are organized under `test/integrated` in subfolders named `01`, `02` etc., with the .xlsx files in the `files` subfolder.
