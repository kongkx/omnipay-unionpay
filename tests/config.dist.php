<?php
// Dist
define('UNIONPAY_DATA_DIR', UNIONPAY_ASSET_DIR . '/dist/data');

define('UNIONPAY_EXPRESS_MER_ID', getenv('UNIONPAY_EXPRESS_MER_ID') ?: '777290058110048');
define('UNIONPAY_WTZ_MER_ID', getenv('UNIONPAY_WTZ_MER_ID') ?: '777290058110097');

define('UNIONPAY_510_ENCRYPT_CERT', UNIONPAY_ASSET_DIR . '/dist/5_1_0_certs/acp_test_enc.cer');
define('UNIONPAY_510_MIDDLE_CERT', UNIONPAY_ASSET_DIR . '/dist/5_1_0_certs/acp_test_middle.cer');
define('UNIONPAY_510_ROOT_CERT', UNIONPAY_ASSET_DIR . '/dist/5_1_0_certs/acp_test_root.cer');
define('UNIONPAY_510_SIGN_CERT', UNIONPAY_ASSET_DIR . '/dist/5_1_0_certs/acp_test_sign.pfx');
define('UNIONPAY_510_CERT_PASSWORD', '000000');
