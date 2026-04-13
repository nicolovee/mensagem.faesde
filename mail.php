<?php

function getClientIp(): string
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'IP desconhecido';
}

function sendRedirectNotification(string $area, string $route, string $whatsappUrl): bool
{
    $to = 'contato@faesde.com';
    $from = 'mensagem.faesde.com.br@faesde.com';

    date_default_timezone_set('America/Sao_Paulo');
    $timestamp = date('d/m/Y H:i:s');

    $ip = getClientIp();
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Não informado';
    $referer = $_SERVER['HTTP_REFERER'] ?? 'Não informado';

    $subject = "Lead encaminhado pelo WhatsApp - $area";
    $body = "Um lead clicou na rota de interesse do site.\r\n" .
            "Área de interesse: $area\r\n" .
            "Rota acessada: /$route\r\n" .
            "WhatsApp: $whatsappUrl\r\n" .
            "Endereço IP: $ip\r\n" .
            "User Agent: $userAgent\r\n" .
            "Referer: $referer\r\n" .
            "Horário: $timestamp\r\n";

    $headers = "From: $from\r\n" .
               "Reply-To: $from\r\n" .
               "X-Mailer: PHP/" . phpversion();

    $sent = @mail($to, $subject, $body, $headers);

    $logEntry = "[$timestamp] rota=/$route area=$area ip=$ip referer=$referer user_agent=" . str_replace("\n", ' ', $userAgent) . "\n";
    @file_put_contents(__DIR__ . '/clicks.log', $logEntry, FILE_APPEND | LOCK_EX);

    return $sent;
}

if (php_sapi_name() !== 'cli' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    http_response_code(400);
    echo 'Este arquivo agora é usado apenas como biblioteca. Use as rotas do index.php.';
}
