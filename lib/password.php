<?php
if (preg_match('/password\.php/', $_SERVER['PHP_SELF'])) { header('location: /'); }

// function to convert string to hexa
function strtohex($x)
{
    $s='';
    foreach (str_split($x) as $c) $s.=sprintf("%02X",ord($c));
    return($s);
}

// set encryption method
$method = 'aes-256-cbc';

// set key, convert to hexa and pack it (size: 64)
$key = "43ikKn8W6ncbLku86Y3d3x02uUUYxwYYv654Ppt8A1wUry0w2L048XAC8MrOC3UB";
$key = pack('H*', strtohex($key));

// function to decrypt data
function mydecrypt($input)
{
    global $method, $key;

    // extract IVs and encrypted data
    $dbiv = substr(base64_decode($input), 0, 16);
    $dbenc = substr(base64_decode($input), 16);

    // decrypt data
    $decrypted = openssl_decrypt($dbenc, $method, $key, true, $dbiv);

    // return password hash
    return base64_decode($decrypted);
}

function mycrypt($hash)
{
    global $method, $key;

    // create random IVs
    $iv = mcrypt_create_iv(16, MCRYPT_RAND);

    // encrypt the hashed password
    $encrypted = openssl_encrypt(base64_encode($hash), $method, $key, true, $iv);

    // return concatened IVs and encrypted password
    return base64_encode($iv.$encrypted);
}
?>
