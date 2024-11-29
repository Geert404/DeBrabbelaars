<?php

// Forked from https://gist.github.com/nichtich/5290675
// Available from https://github.com/Geert404/PushToServer-template#file-deploy-php

$TITLE   = 'Git Deployment Service';
$VERSION = '0.1';

echo <<<EOT
<!DOCTYPE HTML>
<html lang="en-US">
<head>
	<meta charset="UTF-8">
	<title>$TITLE</title>
</head>
<body style="background-color: #000000; color: #FFFFFF; font-weight: bold; padding: 0 10px;">
<pre>
$TITLE
v$VERSION



EOT;

// Check whether the received trigger is from an allowed source

$allowed_ips = array(
	'207.97.227.', '50.57.128.', '108.171.174.', '50.57.231.', '204.232.175.', '192.30.252.', // GitHub
);
$allowed = false;

function exit_if_error($errormessage) {
    header('HTTP/1.1 403 Forbidden');
 	echo "<span style=\"color: #ff0000\">Something went wrong!</span>\n";
    echo "<span style=\"color: #ff0000\">$errormessage</span>\n";
    echo "</pre>\n</body>\n</html>";
    exit;
}

if (!function_exists('apache_request_headers')) {
    $errormessage = 'Cannot see the function apache_request_headers';
    exit_if_error($errormessage);
}

$headers = apache_request_headers();

if (@$headers["X-Forwarded-For"]) {
    $ips = explode(",",$headers["X-Forwarded-For"]);
    $ip  = $ips[0];
} else {
    $ip = $_SERVER['REMOTE_ADDR'];
}

foreach ($allowed_ips as $allow) {
    if (stripos($ip, $allow) !== false) {
        $allowed = true;
        break;
    }
}

if (!$allowed) {
	$errormessage = 'Source is not allowed to trigger update';
    exit_if_error($errormessage);
}

flush();

// Actually run the update

$commands = array(
	'echo $PWD',
	'whoami',
	'git pull',
	'git status',
	'git submodule sync',
	'git submodule update',
	'git submodule status',
    'test -e /usr/share/update-notifier/notify-reboot-required && echo "system restart required"',
);

$output = "\n";

$log = "####### ".date('Y-m-d H:i:s'). " #######\n";

foreach($commands AS $command){
    // Run it
    $tmp = shell_exec("$command 2>&1");
    // Output
    $output .= "<span style=\"color: #6BE234;\">\$</span> <span style=\"color: #729FCF;\">{$command}\n</span>";
    $output .= htmlentities(trim($tmp)) . "\n";

    $log  .= "\$ $command\n".trim($tmp)."\n";
}

$log .= "\n";

file_put_contents ('deploy-log.txt',$log,FILE_APPEND);

echo $output; 

?>
</pre>
</body>
</html>