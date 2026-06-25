<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=UTF-8');

// receive JSON payload or form-encoded POST data from fetch
$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!$data || !is_array($data)) {
    $data = $_POST;
    if (!$data) {
        parse_str($input, $data);
    }
}

if (!$data || !is_array($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$name = trim($data['name'] ?? '');
$phone = trim($data['phone'] ?? '');
$email = trim($data['email'] ?? '');
$service = trim($data['service'] ?? '');
$date = trim($data['date'] ?? '');
$message = trim($data['message'] ?? '');

if (!$name || !$phone || !$email || !$service) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$to = 'info.edgecleaning6@gmail.com';
$subject = 'New cleaning request from website';
$body = "Name: $name\nPhone: $phone\nEmail: $email\nService: $service\nPreferred Date: " . ($date ?: 'N/A') . "\nDetails: " . ($message ?: 'N/A');
$fromEmail = 'r.indikasilva@gmail.com';
$fromName = 'Edge Cleaning';

function smtp_send_mail($host, $port, $username, $password, $to, $subject, $body, $fromEmail, $fromName, $replyTo) {
    $timeout = 30;
    $remote = "ssl://$host:$port";
    $socket = stream_socket_client($remote, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT);

    if (!$socket) {
        return ['success' => false, 'error' => "Connection failed: $errstr ($errno)"];
    }

    stream_set_timeout($socket, $timeout);

    $read = fn() => trim(fgets($socket, 515));
    $write = fn($cmd) => fwrite($socket, $cmd . "\r\n");

    $response = $read();
    if (strpos($response, '220') !== 0) {
        return ['success' => false, 'error' => "SMTP connect error: $response"];
    }

    $write("EHLO localhost");
    $response = $read();
    if (strpos($response, '250') !== 0) {
        return ['success' => false, 'error' => "EHLO error: $response"];
    }
    while (substr($response, 3, 1) === '-') {
        $response = $read();
    }

    $write('AUTH LOGIN');
    $response = $read();
    if (strpos($response, '334') !== 0) {
        return ['success' => false, 'error' => "AUTH LOGIN error: $response"];
    }

    $write(base64_encode($username));
    $response = $read();
    if (strpos($response, '334') !== 0) {
        return ['success' => false, 'error' => "Username error: $response"];
    }

    $write(base64_encode($password));
    $response = $read();
    if (strpos($response, '235') !== 0) {
        return ['success' => false, 'error' => "Password error: $response"];
    }

    $write("MAIL FROM: <{$fromEmail}>");
    $response = $read();
    if (strpos($response, '250') !== 0) {
        return ['success' => false, 'error' => "MAIL FROM error: $response"];
    }

    $write("RCPT TO: <{$to}>");
    $response = $read();
    if (strpos($response, '250') !== 0 && strpos($response, '251') !== 0) {
        return ['success' => false, 'error' => "RCPT TO error: $response"];
    }

    $write('DATA');
    $response = $read();
    if (strpos($response, '354') !== 0) {
        return ['success' => false, 'error' => "DATA error: $response"];
    }

    $headers = [];
    $headers[] = 'From: ' . $fromName . ' <' . $fromEmail . '>';
    $headers[] = 'Reply-To: ' . $replyTo;
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';
    $headers[] = 'Subject: ' . $subject;
    $headers[] = 'To: ' . $to;
    $headers[] = 'Date: ' . date('r');

    $message = implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.\r\n";
    $write($message);

    $response = $read();
    if (strpos($response, '250') !== 0) {
        return ['success' => false, 'error' => "Message send error: $response"];
    }

    $write('QUIT');
    fclose($socket);
    return ['success' => true];
}

$smtpHost = 'smtp.gmail.com';
$smtpPort = 465;
$smtpUser = 'r.indikasilva@gmail.com';
$smtpPass = 'paeg mgve wyva nfxf';

$result = smtp_send_mail($smtpHost, $smtpPort, $smtpUser, $smtpPass, $to, $subject, $body, $fromEmail, $fromName, filter_var($email, FILTER_SANITIZE_EMAIL));

if (!$result['success']) {
    http_response_code(500);
    echo json_encode(['error' => $result['error']]);
    exit;
}

echo json_encode(['success' => true]);
