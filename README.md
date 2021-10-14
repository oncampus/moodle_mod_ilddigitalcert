# Digitales Zertifikat #

## Installation ##

`clone repository into mod/ilddigitalcert/`

For interacting with the blockchain, you need to install the following extensions:
  * https://github.com/sc0Vu/web3.php
  * https://github.com/web3p/ethereum-tx

For creating PDF documents with attached certificate metadata, you need to install the following extensions:
  * https://packagist.org/packages/mpdf/mpdf
  * https://packagist.org/packages/mpdf/qrcode

For encrypting data like private keys with a keyphrase:
  * https://github.com/defuse/php-encryption

Also make sure that PHP extension gmp is installed

The file composer.json is already included in this repository. So simply:

`run "composer install" on command line`

PHP-Encrypt is needed to allow an automated singing process for issued certificates.
To enable the automated process a key has to be generated and stored in moodles filesystem.
  * To generate a key open a command prompt in your moodle directory and run folowing command
`$ vendor/bin/generate-defuse-key`
  * Copy the generated key and save it in .txt file in moodledata/filedir/ilddigitalcert-secret_key.txt.


To enable PDF download, make sure that temporary files directory "mod/ilddigitalcert/vendor/mpdf/mpdf/tmp" is writable

For detaching metadata from PDF files, you need to install Poppler (https://poppler.freedesktop.org/)

`yum install poppler poppler-utils`
