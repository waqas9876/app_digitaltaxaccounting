<?php
// =============================================
// SIMPLE SMTP MAILER — no Composer required
// =============================================

function sendMail(string $to, string $subject, string $htmlBody): bool {
    $host     = SMTP_HOST;
    $port     = SMTP_PORT;
    $user     = SMTP_USER;
    $pass     = SMTP_PASS;
    $from     = SMTP_FROM;
    $fromName = SMTP_FROM_NAME;
    $secure   = SMTP_SECURE; // 'tls' or 'ssl'

    $errno = 0; $errstr = '';

    // Open socket
    $addr   = ($secure === 'ssl') ? "ssl://{$host}" : $host;
    $socket = @fsockopen($addr, $port, $errno, $errstr, 15);
    if (!$socket) {
        error_log("SMTP connect failed: {$errstr} ({$errno})");
        return false;
    }

    stream_set_timeout($socket, 15);

    $read = function() use ($socket) {
        $r = '';
        while ($line = fgets($socket, 515)) {
            $r .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        return $r;
    };

    $cmd = function($c) use ($socket, $read) {
        fputs($socket, $c . "\r\n");
        return $read();
    };

    // Greeting
    $read();

    // EHLO
    $cmd('EHLO ' . (gethostname() ?: 'localhost'));

    // STARTTLS
    if ($secure === 'tls') {
        $r = $cmd('STARTTLS');
        if (substr(trim($r), 0, 3) !== '220') { fclose($socket); return false; }
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket); return false;
        }
        $cmd('EHLO ' . (gethostname() ?: 'localhost'));
    }

    // AUTH LOGIN
    $cmd('AUTH LOGIN');
    $cmd(base64_encode($user));
    $authResp = $cmd(base64_encode($pass));
    if (substr(trim($authResp), 0, 3) !== '235') {
        error_log("SMTP auth failed: {$authResp}");
        fclose($socket);
        return false;
    }

    // Envelope
    $cmd("MAIL FROM: <{$from}>");
    $rcptResp = $cmd("RCPT TO: <{$to}>");
    if (substr(trim($rcptResp), 0, 3) !== '250') {
        error_log("SMTP RCPT failed: {$rcptResp}");
        fclose($socket);
        return false;
    }

    // DATA
    $cmd('DATA');

    $headers  = "Date: " . date('r') . "\r\n";
    $headers .= "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$from}>\r\n";
    $headers .= "To: {$to}\r\n";
    $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "Content-Transfer-Encoding: base64\r\n";

    $body = chunk_split(base64_encode($htmlBody));

    $dataResp = $cmd($headers . "\r\n" . $body . "\r\n.");
    $cmd('QUIT');
    fclose($socket);

    return substr(trim($dataResp), 0, 3) === '250';
}
