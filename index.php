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
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            main {
                background: white;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0,0,0,.3);
                padding: 60px 40px;
                max-width: 700px;
                text-align: center;
            }
            h1 {
                font-size: 32px;
                color: #333;
                margin-bottom: 15px;
                font-weight: 700;
            }
            p {
                color: #666;
                font-size: 16px;
                margin-bottom: 40px;
                line-height: 1.6;
            }
            .buttons-grid {
                display: grid;
                grid-template-columns: 1fr;
                gap: 15px;
                margin-top: 30px;
            }
            @media (min-width: 600px) {
                .buttons-grid {
                    grid-template-columns: 1fr 1fr;
                }
            }
            .btn {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 25px 20px;
                text-decoration: none;
                color: white;
                border: none;
                border-radius: 12px;
                font-size: 15px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                min-height: 120px;
            }
            .btn:hover {
                transform: translateY(-5px);
                box-shadow: 0 15px 35px rgba(0,0,0,.2);
            }
            .btn-admin { background: linear-gradient(135deg, #667eea, #764ba2); }
            .btn-maintenance { background: linear-gradient(135deg, #f093fb, #f5576c); }
            .btn-commercial { background: linear-gradient(135deg, #4facfe, #00f2fe); }
            .btn-environment { background: linear-gradient(135deg, #43e97b, #38f9d7); }
            .btn-safety { background: linear-gradient(135deg, #fa709a, #fee140); }
            .btn-label { display: block; font-size: 14px; margin-bottom: 8px; opacity: 0.9; }
            .btn-title { display: block; font-size: 18px; font-weight: 700; }
        </style>
    </head>
    <body>
        <main>
            <img src="./img/logo.png" alt="FAESDE Logo" style="max-width: 100px; margin-bottom: 20px; height: auto;">
            <h1>Clique na sua área</h1>
            <p>Selecione o departamento ou interesse para falar conosco pelo WhatsApp</p>
            <div class="buttons-grid">
                <a href="<?= $baseUrl ?>/administrativo" class="btn btn-admin">
                    <span class="btn-label">📋</span>
                    <span class="btn-title">Administrativo</span>
                </a>
                <a href="<?= $baseUrl ?>/manutencao-industrial" class="btn btn-maintenance">
                    <span class="btn-label">🔧</span>
                    <span class="btn-title">Manutenção Industrial</span>
                </a>
                <a href="<?= $baseUrl ?>/comercial" class="btn btn-commercial">
                    <span class="btn-label">💼</span>
                    <span class="btn-title">Comercial</span>
                </a>
                <a href="<?= $baseUrl ?>/meio-ambiente" class="btn btn-environment">
                    <span class="btn-label">🌱</span>
                    <span class="btn-title">Meio Ambiente</span>
                </a>
                <a href="<?= $baseUrl ?>/seguranca-trabalho" class="btn btn-safety" style="grid-column: 1 / -1;">
                    <span class="btn-label">⛑️</span>
                    <span class="btn-title">Segurança do Trabalho</span>
                </a>
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
