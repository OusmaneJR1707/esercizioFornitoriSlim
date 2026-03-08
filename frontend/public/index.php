<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../App/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$app = AppFactory::create();

$app->addRoutingMiddleware(); // Importa il Router originale di Slim che analizza l'URL richiesto e cerca una rotta corrispondente
$app->setBasePath('/esercizioFornitoriSlim/frontend');
$app->addErrorMiddleware(true, true, true);

$app->get('/', function (Request $request, Response $response) {
    // Restituisce una risposta che reindirizza a /api con codice stato 302 (temporaneo)
    return $response
        ->withHeader('Location', '/esercizioFornitoriSlim/frontend/homepage')
        ->withStatus(302);
});

$app->get('/homepage', function (Request $request, Response $response) {
    $templatePath = __DIR__ . '/../Templates/homepage.php';

    if (file_exists($templatePath)) {
        $html = file_get_contents($templatePath);
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    $response->getBody()->write("Error: template not found");
    return $response->withStatus(404);
});

$app->get('/login', function (Request $request, Response $response) {
    $templatePath =  __DIR__ . '/../templates/login.php';
    
    if (file_exists($templatePath)) {
        ob_start(); // Inizia a "registrare" l'output
        include $templatePath; // Esegue il codice PHP del template
        $html = ob_get_clean(); // Salva l'output in una variabile
        
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    $response->getBody()->write("Error: template not found");
    return $response->withStatus(404);
});

$app->post('/login', function (Request $request, Response $response) {
    $data = $request->getParsedBody();

    // Chiamata all'API Backend (Pura) per verificare le credenziali
    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data), // prende l'array associativo ricevuto dal form e lo trasforma in una stringa formattata per URL
        ],
    ];

    // Trasforma le opzioni in una configurazione tecnica che 
    // spiega a file_get_contents come eseguire la richiesta POST.
    $context  = stream_context_create($options); 
    
    // URL assoluto dell'API del backend
    $result = @file_get_contents('http://localhost/esercizioFornitoriSlim/backend/api/auth/verify', false, $context); // Spedisce la richiesta all'API
    $resData = json_decode($result, true);

    if ($resData && $resData['success']) {
        // Se l'API dice OK, creiamo la sessione qui nel Frontend
        $_SESSION['user'] = $resData['data'];
        // Reindirizziamo all'area admin
        return $response->withHeader('Location', '/esercizioFornitoriSlim/frontend/admin')->withStatus(302);
    }

    // Se fallisce, torna al login con un errore
    return $response->withHeader('Location', '/esercizioFornitoriSlim/frontend/login?error=1')->withStatus(302);
});

$app->get('/admin', function (Request $request, Response $response) {
    // Controllo sicurezza: se non c'è la sessione, fuori!
    if (!isset($_SESSION['user'])) {
        return $response->withHeader('Location', '/esercizioFornitoriSlim/frontend/login')->withStatus(302);
    }

    $templatePath =  __DIR__ . '/../templates/admin.php';
    
    if (file_exists($templatePath)) {
        ob_start(); // Inizia a "registrare" l'output
        include $templatePath; // Esegue il codice PHP del template
        $html = ob_get_clean(); // Salva l'output in una variabile
        
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    $response->getBody()->write("Error: template not found");
    return $response->withStatus(404);
});

// Rotta per il Logout
$app->get('/logout', function (Request $request, Response $response) {
    session_destroy();
    return $response->withHeader('Location', '/esercizioFornitoriSlim/frontend/login')->withStatus(302);
});

$app->run();



