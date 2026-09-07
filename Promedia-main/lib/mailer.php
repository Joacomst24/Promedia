<?php

declare(strict_types=1);

function smtpReadResponse($socket, array $allowedCodes): bool
{
    $response = '';

    while (!feof($socket)) {
        $line = fgets($socket, 515);
        if ($line === false) {
            break;
        }

        $response .= $line;

        // Multi-line SMTP responses keep '-' as separator until final line.
        if (strlen($line) >= 4 && $line[3] === ' ') {
            break;
        }
    }

    if ($response === '' || strlen($response) < 3) {
        return false;
    }

    $code = (int)substr($response, 0, 3);

    return in_array($code, $allowedCodes, true);
}

function smtpWriteCommand($socket, string $command): bool
{
    return fwrite($socket, $command . "\r\n") !== false;
}

function smtpSendPlainTextEmail(string $to, string $subject, string $body, string $fromEmail, string $fromName): bool
{
    $host = trim((string)(getenv('SMTP_HOST') ?: ''));
    if ($host === '') {
        return false;
    }

    $port = (int)(getenv('SMTP_PORT') ?: 587);
    $encryption = strtolower(trim((string)(getenv('SMTP_ENCRYPTION') ?: 'tls')));
    $username = trim((string)(getenv('SMTP_USER') ?: ''));
    $password = (string)(getenv('SMTP_PASS') ?: '');
    $heloHost = trim((string)(getenv('SMTP_HELO') ?: 'localhost'));
    $timeout = (int)(getenv('SMTP_TIMEOUT') ?: 15);

    if ($port <= 0) {
        $port = 587;
    }
    if ($timeout <= 0) {
        $timeout = 15;
    }

    $transportHost = $encryption === 'ssl' ? 'ssl://' . $host : $host;
    $socket = @stream_socket_client(
        $transportHost . ':' . $port,
        $errno,
        $errstr,
        $timeout,
        STREAM_CLIENT_CONNECT
    );

    if ($socket === false) {
        return false;
    }

    stream_set_timeout($socket, $timeout);

    if (!smtpReadResponse($socket, [220])) {
        fclose($socket);
        return false;
    }

    if (!smtpWriteCommand($socket, 'EHLO ' . $heloHost) || !smtpReadResponse($socket, [250])) {
        fclose($socket);
        return false;
    }

    if ($encryption === 'tls') {
        if (!smtpWriteCommand($socket, 'STARTTLS') || !smtpReadResponse($socket, [220])) {
            fclose($socket);
            return false;
        }

        $cryptoEnabled = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        if ($cryptoEnabled !== true) {
            fclose($socket);
            return false;
        }

        if (!smtpWriteCommand($socket, 'EHLO ' . $heloHost) || !smtpReadResponse($socket, [250])) {
            fclose($socket);
            return false;
        }
    }

    if ($username !== '' || $password !== '') {
        if (!smtpWriteCommand($socket, 'AUTH LOGIN') || !smtpReadResponse($socket, [334])) {
            fclose($socket);
            return false;
        }

        if (!smtpWriteCommand($socket, base64_encode($username)) || !smtpReadResponse($socket, [334])) {
            fclose($socket);
            return false;
        }

        if (!smtpWriteCommand($socket, base64_encode($password)) || !smtpReadResponse($socket, [235])) {
            fclose($socket);
            return false;
        }
    }

    if (!smtpWriteCommand($socket, 'MAIL FROM:<' . $fromEmail . '>') || !smtpReadResponse($socket, [250])) {
        fclose($socket);
        return false;
    }

    if (!smtpWriteCommand($socket, 'RCPT TO:<' . $to . '>') || !smtpReadResponse($socket, [250, 251])) {
        fclose($socket);
        return false;
    }

    if (!smtpWriteCommand($socket, 'DATA') || !smtpReadResponse($socket, [354])) {
        fclose($socket);
        return false;
    }

    $headers = [
        'Date: ' . date(DATE_RFC2822),
        'From: ' . $fromName . ' <' . $fromEmail . '>',
        'To: <' . $to . '>',
        'Subject: ' . $subject,
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ];

    $safeBody = str_replace(["\r\n.", "\n.", "\r."], ["\r\n..", "\n..", "\r.."], $body);
    $data = implode("\r\n", $headers) . "\r\n\r\n" . $safeBody . "\r\n.";

    if (fwrite($socket, $data . "\r\n") === false || !smtpReadResponse($socket, [250])) {
        fclose($socket);
        return false;
    }

    smtpWriteCommand($socket, 'QUIT');
    smtpReadResponse($socket, [221]);
    fclose($socket);

    return true;
}

function sendAccountApprovalEmail(string $toEmail, string $fullName, int $role): bool
{
    $to = trim($toEmail);
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $roleLabel = match ($role) {
        1 => 'Profesor',
        2 => 'Administrador',
        default => 'Alumno',
    };
    $safeName = trim($fullName) !== '' ? trim($fullName) : 'Usuario';
    $subject = 'Promedia - Cuenta habilitada';

    $messageLines = [
        'Hola ' . $safeName . ',',
        '',
        'Tu cuenta de Promedia ya fue habilitada por el superior.',
        'Rol asignado: ' . $roleLabel,
        '',
        'Ya podés ingresar con tu DNI y clave en login.php.',
        '',
        'Equipo Promedia',
    ];

    $message = implode("\r\n", $messageLines);

    $from = getenv('APP_MAIL_FROM') ?: 'no-reply@promedia.local';
    $fromName = getenv('APP_MAIL_FROM_NAME') ?: 'Promedia';

    if (smtpSendPlainTextEmail($to, $subject, $message, $from, $fromName)) {
        return true;
    }

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . $fromName . ' <' . $from . '>',
    ];

    return @mail($to, $subject, $message, implode("\r\n", $headers));
}
