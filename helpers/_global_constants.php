<?php
define('FILES_DISK', 'local');
define('FILE_MAX_SIZE', 2048);
define('FILE_TYPES', [
    'image/jpeg',
    'image/png',
    'application/pdf',
    'text/csv',
    'application/msword',
    'application/vnd.ms-excel',
    'application/zip',
    'application/vnd.rar',
    'application/x-zip-compressed',
    'application/x-7z-compressed',
    'multipart/x-zip',
]);

define('CERTIFICATES', [
    "CEP",
    "COPP",
    "DMF",
    "EIR",
    "EU-GMP",
    "FDA",
    "GMP",
    "US-DMD",
    "US-DMF",
    "US-FDA",
    "WC",
    "WHO-GMP",
]);

define('TAX_REGEX', '/^(?:\d{10}|\d{10}-\d{3})$/');
define('CLEARANCE_NO_REGEX', '/^\d{12}$/');

define('VCB_RATE_TARGET', 'sell');

define('JS_ORDER_DATE', 'order_date');
define('JS_DATE_DEPENDENT', 'form.order_dependent');
define('JS_SELECTION_ROOT', 'form.company_id');

define('SPACING', ' ');