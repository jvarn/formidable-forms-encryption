# Formidable Forms Encryption
A Wordpress plugin to enable encrypting and decrypting of data entered using Formidable Forms. Requires Advanced Custom Fields (ACF) Plugin.

## Initial configuration
The following lines need to be added to wp-config.php, substituting the strings as described below.

`define('FORMIDABLE_SALT', 'string);`
`define('FORMIDABLE_METHOD', 'string');`
`define('FORMIDABLE_OPTIONS', 0);`
`define('FORMIDABLE_IV', 'string');`

### FORMIDABLE_SALT
Generate a SALT value (choose any one and rename FORMIDABLE_SALT).

### FORMIDABLE_METHOD
Choose an appropriate encryption method.
e.g. AES-256-CBC

### FORMIDABLE_OPTIONS
Is 0, here’s an explanation of openssl options.

### FORMIDABLE_IV
“A non-NULL Initialization Vector” of a particular length according to chosen encryption method
> “a random string with a length that must match the length required by your chosen cipher method. How do we know the iv length required by the chosen cipher method? We use the openssl_cipher_iv_length($cipher) function to find out.”

More details in this [blog post](https://abuyasmeen.com/formidable-forms-encryption/).
