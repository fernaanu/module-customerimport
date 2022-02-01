# WT1_CustomerImport

WT1_CustomerImport module used to import customers from a sample CSV or JSON.

## Installation

In magento root directory, execute:
```bash
bin/magento module:enable WT1_CustomerImport
bin/magento s:up
bin/magento s:d:c
bin/magento c:f
bin/magento c:c
```

## Usage

To import from the CSV and the JSON respectively the user would execute either one of the following:
```bash
bin/magento wt:customer:import sample-csv sample.csv
bin/magento wt:customer:import sample-json sample.json
```

Location for Sample files: 
```bash
var/import/ directory.
```