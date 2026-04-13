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
        <title>FAESDE - Contate-nos</title>
        <link rel="icon" href="./favicon.ico" type="image/x-icon">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                background: #f5f5f5;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            main {
                background: white;
                border-radius: 12px;
                box-shadow: 0 2px 12px rgba(0,0,0,.1);
                padding: 40px 30px;
                max-width: 500px;
                text-align: center;
            }
            img {
                max-width: 80px;
                height: auto;
                margin-bottom: 25px;
            }
            h1 {
                font-size: 24px;
                color: #222;
                margin-bottom: 8px;
                font-weight: 600;
            }
            p {
                color: #666;
                font-size: 14px;
                margin-bottom: 30px;
                line-height: 1.5;
            }
            .buttons-grid {
                display: grid;
                grid-template-columns: 1fr;
                gap: 10px;
            }
            .btn {
                display: block;
                padding: 14px 20px;
                text-decoration: none;
                color: white;
                border: none;
                border-radius: 6px;
                font-size: 14px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.2s;
            }
            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0,0,0,.15);
            }
            .btn-admin { background: #2c3e50; }
            .btn-maintenance { background: #e74c3c; }
            .btn-commercial { background: #3498db; }
            .btn-environment { background: #27ae60; }
            .btn-safety { background: #f39c12; }
        </style>
    </head>
    <body>
        <main>
            <img src="./img/logo.png" alt="FAESDE Logo">
            <h1>Clique na sua área</h1>
            <p>Selecione o departamento para conversar conosco</p>
            <div class="buttons-grid">
                <a href="<?= $baseUrl ?>/administrativo" class="btn btn-admin">Administrativo</a>
                <a href="<?= $baseUrl ?>/manutencao-industrial" class="btn btn-maintenance">Manutenção Industrial</a>
                <a href="<?= $baseUrl ?>/comercial" class="btn btn-commercial">Comercial</a>
                <a href="<?= $baseUrl ?>/meio-ambiente" class="btn btn-environment">Meio Ambiente</a>
                <a href="<?= $baseUrl ?>/seguranca-trabalho" class="btn btn-safety">Segurança do Trabalho</a>
            </div>
        </main>
    </body>
    </html>
    <?php
}

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requestPath = trim($requestPath, '/');

if ($requestPath === '' || $requestPath === 'index.php') {
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
