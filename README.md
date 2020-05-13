# Digitales Zertifikat #

## Installation ##

`clone repository into mod/ilddigitalcert/`

For interacting with the blockchain, you need to install the following extensions:
  * https://github.com/sc0Vu/web3.php
  * https://github.com/web3p/ethereum-tx

For creating PDF documents with attached certificate metadata, you need to install the following extensions:
  * https://packagist.org/packages/mpdf/mpdf
  * https://packagist.org/packages/mpdf/qrcode

Also make sure that PHP extension gmp is installed
  
The file composer.json is already included in this repository. So simply run:

`run "composer install" on command line`

For detaching metadata from PDF files, you need to install Poppler (https://poppler.freedesktop.org/)

`yum install poppler poppler-utils`
