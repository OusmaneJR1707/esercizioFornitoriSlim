<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../App/bootstrap.php';

$app = AppFactory::create();

// Middleware per il CORS
$app->add(function ($request, $handler) {
    $response = $handler->handle($request); // Passa la richiesta alla giusta rotta e prende la risposta
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

// Gestione delle richieste OPTIONS (pre-flight), richieste di prova inviate dal browser prima di inviare quella vera e propria
$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

$app->addBodyParsingMiddleware(); // permette a Slim di leggere i dati PUT e i JSON

$app->addRoutingMiddleware(); // Importa il Router originale di Slim che analizza l'URL richiesto e cerca una rotta corrispondente
$app->setBasePath('/esercizioFornitoriSlim/backend');

$app->addErrorMiddleware(true, true, true);

try {
    $dsn = "mysql:host=" . HOST_DB . ";dbname=" . NAME_DB . ";charset=utf8mb4";

    $options = [
        PDO::ATTR_PERSISTENT => false,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
    ]; 

    $pdo = new PDO($dsn, USER_DB, PASS_DB, $options);
} catch (PDOException $e){
    die(json_encode(["error" => "Errore di connessione al database: " . $e->getMessage()]));
}

$queries = [
    1 => "SELECT DISTINCT p.pid, p.pnome FROM Pezzi p JOIN Catalogo c ON p.pid = c.pid",
    2 => "SELECT f.fid, f.fnome FROM Fornitori f WHERE NOT EXISTS (SELECT * FROM Pezzi p WHERE NOT EXISTS (SELECT * FROM Catalogo c WHERE c.fid = f.fid AND c.pid = p.pid))",
    3 => "SELECT f.fid, f.fnome FROM Fornitori f WHERE NOT EXISTS (SELECT * FROM Pezzi p WHERE p.colore = 'rosso' AND NOT EXISTS (SELECT * FROM Catalogo c WHERE c.fid = f.fid AND c.pid = p.pid))",
    4 => "SELECT p.pid, p.pnome FROM Pezzi p JOIN Catalogo c ON p.pid = c.pid JOIN Fornitori f ON c.fid = f.fid WHERE f.fnome = 'Acme' AND p.pid NOT IN (SELECT c2.pid FROM Catalogo c2 JOIN Fornitori f2 ON c2.fid = f2.fid WHERE f2.fnome != 'Acme')",
    5 => "SELECT DISTINCT c1.fid FROM Catalogo c1 WHERE c1.costo > (SELECT AVG(c2.costo) FROM Catalogo c2 WHERE c2.pid = c1.pid)",
    6 => "SELECT c1.pid, c1.fid, f.fnome FROM Catalogo c1 JOIN Fornitori f ON c1.fid = f.fid WHERE c1.costo = (SELECT MAX(c2.costo) FROM Catalogo c2 WHERE c2.pid = c1.pid)",
    7 => "SELECT DISTINCT fid FROM Catalogo WHERE fid NOT IN (SELECT c.fid FROM Catalogo c JOIN Pezzi p ON c.pid = p.pid WHERE p.colore != 'rosso')",
    8 => "SELECT DISTINCT c1.fid FROM Catalogo c1 JOIN Pezzi p1 ON c1.pid = p1.pid WHERE p1.colore = 'rosso' AND c1.fid IN (SELECT c2.fid FROM Catalogo c2 JOIN Pezzi p2 ON c2.pid = p2.pid WHERE p2.colore = 'verde')",
    9 => "SELECT DISTINCT c.fid FROM Catalogo c JOIN Pezzi p ON c.pid = p.pid WHERE p.colore IN ('rosso', 'verde')",
    10 => "SELECT pid FROM Catalogo GROUP BY pid HAVING COUNT(DISTINCT fid) >= 2"
];

$app->get('/', function (Request $request, Response $response) {
    // Restituisce una risposta che reindirizza a /api con codice stato 302 (temporaneo)
    return $response
        ->withHeader('Location', '/esercizioFornitoriSlim/backend/api')
        ->withStatus(302);
});

$app->get('/api', function (Request $request, Response $response) use ($queries){
    $endpoints = [];
    foreach ($queries as $id => $sql) {
        $endpoints["query_$id"] = "api/" . $id;
    }

    $payload = [
        "message" => "Welcome to the Catalague API",
        "endpoints" => $endpoints
    ];

    $response->getBody()->write(json_encode($payload, JSON_PRETTY_PRINT));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});

$app->get('/api/catalogo', function ($request, $response) use ($pdo) {
    // Leggiamo un eventuale parametro 'fid' dalla query string (?fid=101)
    $fid = $request->getQueryParams()['fid'] ?? null;

    if ($fid) {
        // Query per il FORNITORE: vede solo i suoi pezzi
        $stmt = $pdo->prepare("
            SELECT c.pid, p.pnome, p.colore, c.costo 
            FROM catalogo c 
            JOIN pezzi p ON c.pid = p.pid 
            WHERE c.fid = ?
        ");
        $stmt->execute([$fid]);
    } else {
        // Query per l'ADMIN: vede tutto
        $stmt = $pdo->query("
            SELECT c.fid, f.fnome, c.pid, p.pnome, c.costo 
            FROM catalogo c 
            JOIN fornitori f ON c.fid = f.fid
            JOIN pezzi p ON c.pid = p.pid
        ");
    }
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response->getBody()->write(json_encode(["success" => true, "data" => $data]));
    return $response->withHeader('Content-Type', 'application/json');
});

// --- INSERISCI (POST) ---
$app->post('/api/catalogo', function ($request, $response) use ($pdo) {
    $data = $request->getParsedBody();
    $stmt = $pdo->prepare("INSERT INTO catalogo (fid, pid, costo) VALUES (?, ?, ?)");
    try {
        $stmt->execute([$data['fid'], $data['pid'], $data['costo']]);
        $response->getBody()->write(json_encode(["success" => true]));
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(["success" => false, "error" => "Errore di inserimento (chiavi duplicate?)"]));
    }
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/api/product', function ($request, $response) use ($pdo) {
    $stmt = $pdo->query("SELECT * FROM pezzi");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response->getBody()->write(json_encode(["success" => true, "data" => $data]));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/api/product', function ($request, $response) use ($pdo) {
    $data = $request->getParsedBody();
    $stmt = $pdo->prepare("INSERT INTO pezzi (pnome, colore) VALUES (?, ?)");
    try {
        $stmt->execute([$data['pnome'], $data['colore']]);
        
        // AGGIUNTA FONDAMENTALE: Restituisce l'ID generato automaticamente
        $newId = $pdo->lastInsertId();
        $response->getBody()->write(json_encode(["success" => true, "pid" => $newId]));
        
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(["success" => false, "error" => $e->getMessage()]));
    }
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/api/product/{id}', function ($request, $response, $args) use ($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM pezzi WHERE pid = :id");
    $stmt->execute(['id' => $args['id']]);
    $data = $stmt->fetch();

    $result = $data ? ["success" => true, "data" => $data] : ["success" => false, "error" => "Product not found"];
    $response->getBody()->write(json_encode($result));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->put('/api/product/{id}', function ($request, $response, $args) use ($pdo) {
    $data = $request->getParsedBody();
    $stmt = $pdo->prepare("UPDATE pezzi SET pnome = ?, colore = ? WHERE pid = ?");
    $stmt->execute([$data['pnome'], $data['colore'], $args['id']]);
    $response->getBody()->write(json_encode(["success" => true]));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->delete('/api/product/{id}', function ($request, $response, $args) use ($pdo) {
    try {
        // Iniziamo la transazione: da qui in poi, o tutto o niente
        $pdo->beginTransaction();

        // 1. Prima eliminiamo tutti i riferimenti nel catalogo (le dipendenze)
        $stmtCatalogo = $pdo->prepare("DELETE FROM catalogo WHERE pid = ?");
        $stmtCatalogo->execute([$args['id']]);

        // 2. Poi eliminiamo il pezzo vero e proprio dall'anagrafica
        $stmtPezzo = $pdo->prepare("DELETE FROM pezzi WHERE pid = ?");
        $stmtPezzo->execute([$args['id']]);

        // Confermiamo le modifiche al database
        $pdo->commit();
        
        $response->getBody()->write(json_encode(["success" => true]));
    } catch (PDOException $e) {
        // Se qualcosa va storto, annulliamo tutto
        $pdo->rollBack();
        $response->getBody()->write(json_encode(["success" => false, "error" => "Errore di eliminazione: " . $e->getMessage()]));
    }
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/api/supplier', function ($request, $response) use ($pdo) {
    $stmt = $pdo->query("SELECT * FROM fornitori");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response->getBody()->write(json_encode(["success" => true, "data" => $data]));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/api/supplier', function ($request, $response) use ($pdo) {
    $data = $request->getParsedBody();
    
    // Rimosso il 'fid' dalla query, ci pensa il database!
    $stmt = $pdo->prepare("INSERT INTO fornitori (fnome, indirizzo) VALUES (?, ?)");
    try {
        $stmt->execute([$data['fnome'], $data['indirizzo']]);
        
        // Restituiamo il nuovo ID creato per sicurezza
        $newId = $pdo->lastInsertId();
        $response->getBody()->write(json_encode(["success" => true, "fid" => $newId]));
        
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(["success" => false, "error" => $e->getMessage()]));
    }
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/api/supplier/{id}', function ($request, $response, $args) use ($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM fornitori WHERE fid = :id");
    $stmt->execute(['id' => $args['id']]);
    $data = $stmt->fetch();
    
    $result = $data ? ["success" => true, "data" => $data] : ["success" => false, "error" => "Supplier not found"];
    $response->getBody()->write(json_encode($result));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->put('/api/supplier/{fid}', function ($request, $response, $args) use ($pdo) {
    $data = $request->getParsedBody();
    $stmt = $pdo->prepare("UPDATE fornitori SET fnome = ?, indirizzo = ? WHERE fid = ?");
    $stmt->execute([$data['fnome'], $data['indirizzo'], $args['fid']]);
    $response->getBody()->write(json_encode(["success" => true]));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->delete('/api/supplier/{id}', function ($request, $response, $args) use ($pdo) {
    try {
        $pdo->beginTransaction();

        // 1. Prima eliminiamo i suoi prodotti in vendita
        $stmtCatalogo = $pdo->prepare("DELETE FROM catalogo WHERE fid = ?");
        $stmtCatalogo->execute([$args['id']]);

        // 2. Poi eliminiamo l'azienda dall'anagrafica
        $stmtFornitore = $pdo->prepare("DELETE FROM fornitori WHERE fid = ?");
        $stmtFornitore->execute([$args['id']]);

        $pdo->commit();
        
        $response->getBody()->write(json_encode(["success" => true]));
    } catch (PDOException $e) {
        $pdo->rollBack();
        $response->getBody()->write(json_encode(["success" => false, "error" => "Errore di eliminazione: " . $e->getMessage()]));
    }
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/api/auth/verify', function ($request, $response) use ($pdo) {
    $params = $request->getParsedBody();
    $user = $params['username'] ?? '';
    $pass = $params['password'] ?? '';

    $stmt = $pdo->prepare("SELECT uid, username, ruolo, fid FROM utenti WHERE username = ? AND password = ?");
    $stmt->execute([$user, $pass]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    $result = $userData ? ["success" => true, "data" => $userData] : ["success" => false, "error" => "No user found"];
    $response->getBody()->write(json_encode($result));
    return $response->withHeader('Content-Type', 'application/json');
});

// --- MODIFICA (PUT) ---
$app->put('/api/catalogo/{fid}/{pid}', function ($request, $response, $args) use ($pdo) {
    $data = $request->getParsedBody();
    // Aggiorniamo solo il costo, fid e pid sono le chiavi primarie
    $stmt = $pdo->prepare("UPDATE catalogo SET costo = ? WHERE fid = ? AND pid = ?");
    $stmt->execute([$data['costo'], $args['fid'], $args['pid']]);
    
    // Controlliamo se ha modificato davvero qualcosa
    if ($stmt->rowCount() > 0) {
        $response->getBody()->write(json_encode(["success" => true]));
    } else {
        $response->getBody()->write(json_encode(["success" => false, "error" => "Nessuna modifica effettuata o record non trovato"]));
    }
    return $response->withHeader('Content-Type', 'application/json');
});

// --- ELIMINA (DELETE) ---
$app->delete('/api/catalogo/{fid}/{pid}', function ($request, $response, $args) use ($pdo) {
    $stmt = $pdo->prepare("DELETE FROM catalogo WHERE fid = ? AND pid = ?");
    $stmt->execute([$args['fid'], $args['pid']]);
    $response->getBody()->write(json_encode(["success" => true]));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/api/{id}', function (Request $request, Response $response, array $args) use ($pdo, $queries) {
    $id = (int) $args['id'];

    if(!isset($queries[$id])) {
        $errorPayload = [
            "success" => false, 
            "error" => "Query not found. Please insert an ID between 1 and 10"
        ];

        $response->getBody()->write(json_encode($errorPayload, JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    $params = $request->getQueryParams(); // legge le query string

    // Preparazione dei parametri per la paginazione
    $page = isset($params['page']) ? (int)$params['page'] : 1;
    $limit = isset($params['limit']) ? (int)$params['limit'] : 10;
    $offset = ($page - 1) * $limit;

    try {
        $sqlConteggio = "SELECT COUNT(*) as totale FROM ($queries[$id]) as subquery";
        $stmtConteggio = $pdo->query($sqlConteggio);
        $totalRecords = $stmtConteggio->fetchColumn(); // restituisce direttamente il valore contenuto nella prima colonna senza metterlo nell'oggetto o nell'array

        $sql = $queries[$id] . " LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetchAll();

        $result = [
            "success" => true,
            "pagination" => [
                "total_records" => $totalRecords,
                "current_page" => $page,
                "limit" => $limit
            ],
            "data" => $data
        ];
    } catch (PDOException $e) {
        $result = [
            "success" => false, 
            "error" => $e->getMessage()
        ];
    }

    $response->getBody()->write(json_encode($result, JSON_PRETTY_PRINT));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});

$app->run();



