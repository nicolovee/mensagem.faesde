<?php

$routes = [
    'administrativo' => 'Administrativo',
    'manutencao-industrial' => 'Manutenção Industrial',
    'comercial' => 'Comercial',
    'meio-ambiente' => 'Meio Ambiente',
    'seguranca-trabalho' => 'Segurança do Trabalho',
];

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

function sendRedirectNotification(string $area, string $route, string $whatsappUrl): void
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

    @mail($to, $subject, $body, $headers);

    $logEntry = "[$timestamp] rota=/$route area=$area ip=$ip referer=$referer user_agent=" . str_replace("\n", ' ', $userAgent) . "\n";
    @file_put_contents(__DIR__ . '/clicks.log', $logEntry, FILE_APPEND | LOCK_EX);
}

function renderPage(array $routes): void
{
    $baseUrl = htmlspecialchars($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'], ENT_QUOTES, 'UTF-8');
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Mensagem FAESDE</title>
        <link rel="icon" href="./favicon.ico" type="image/x-icon">
        <style>
            body { font-family: Arial, sans-serif; background: #f8f9fa; margin: 0; padding: 24px; color: #212529; }
            main { max-width: 640px; margin: 0 auto; background: #fff; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,.08); padding: 32px; }
            h1 { margin-top: 0; }
            ul { padding-left: 1.2rem; }
            a { color: #155724; text-decoration: none; }
            a:hover { text-decoration: underline; }
        </style>
    </head>
    <body>
        <main>
            <h1>Redirecionamento inteligente FAESDE</h1>
            <p>Use uma das rotas de interesse do e-mail para ser redirecionado ao WhatsApp.</p>
            <p>Rotas válidas:</p>
            <ul>
                <?php foreach ($routes as $slug => $label): ?>
                    <li><a href="<?= $baseUrl . '/' . htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>">/<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?></a> — <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
            <p>Se você abriu esta página diretamente, clique em uma das rotas acima ou use o link no e-mail.</p>
        </main>
    </body>
    </html>
    <?php
}

$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestPath = trim($requestPath, '/');

if ($requestPath === '') {
    renderPage($routes);
    exit;
}

if (!array_key_exists($requestPath, $routes)) {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>404 Não encontrado</title></head><body><h1>404 - Rota não encontrada</h1><p>Use uma rota válida do e-mail.</p></body></html>';
    exit;
}

$area = $routes[$requestPath];
$phoneNumber = '552722378054';
$message = "Olá, tenho interesse em $area.";
$whatsappUrl = 'https://api.whatsapp.com/send?phone=' . rawurlencode($phoneNumber) . '&text=' . rawurlencode($message);

sendRedirectNotification($area, $requestPath, $whatsappUrl);

header('Location: ' . $whatsappUrl);
exit;
