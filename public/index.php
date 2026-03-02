<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../App/bootstrap.php';

$app = AppFactory::create();

$app->addRoutingMiddleware();
$app->setBasePath('/verificaasorpresa');

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
    1 => "SELECT DISTINCT p.pnome FROM Pezzi p JOIN Catalogo c ON p.pid = c.pid",
    2 => "SELECT f.fnome FROM Fornitori f WHERE NOT EXISTS (SELECT * FROM Pezzi p WHERE NOT EXISTS (SELECT * FROM Catalogo c WHERE c.fid = f.fid AND c.pid = p.pid))",
    3 => "SELECT f.fnome FROM Fornitori f WHERE NOT EXISTS (SELECT * FROM Pezzi p WHERE p.colore = 'rosso' AND NOT EXISTS (SELECT * FROM Catalogo c WHERE c.fid = f.fid AND c.pid = p.pid))",
    4 => "SELECT p.pnome FROM Pezzi p JOIN Catalogo c ON p.pid = c.pid JOIN Fornitori f ON c.fid = f.fid WHERE f.fnome = 'Acme' AND p.pid NOT IN (SELECT c2.pid FROM Catalogo c2 JOIN Fornitori f2 ON c2.fid = f2.fid WHERE f2.fnome != 'Acme')",
    5 => "SELECT DISTINCT c1.fid FROM Catalogo c1 WHERE c1.costo > (SELECT AVG(c2.costo) FROM Catalogo c2 WHERE c2.pid = c1.pid)",
    6 => "SELECT c1.pid, f.fnome FROM Catalogo c1 JOIN Fornitori f ON c1.fid = f.fid WHERE c1.costo = (SELECT MAX(c2.costo) FROM Catalogo c2 WHERE c2.pid = c1.pid)",
    7 => "SELECT DISTINCT fid FROM Catalogo WHERE fid NOT IN (SELECT c.fid FROM Catalogo c JOIN Pezzi p ON c.pid = p.pid WHERE p.colore != 'rosso')",
    8 => "SELECT DISTINCT c1.fid FROM Catalogo c1 JOIN Pezzi p1 ON c1.pid = p1.pid WHERE p1.colore = 'rosso' AND c1.fid IN (SELECT c2.fid FROM Catalogo c2 JOIN Pezzi p2 ON c2.pid = p2.pid WHERE p2.colore = 'verde')",
    9 => "SELECT DISTINCT c.fid FROM Catalogo c JOIN Pezzi p ON c.pid = p.pid WHERE p.colore IN ('rosso', 'verde')",
    10 => "SELECT pid FROM Catalogo GROUP BY pid HAVING COUNT(DISTINCT fid) >= 2"
];

$app->get('/api', function (Request $request, Response $response) use ($queries){
    $endpoints = [];
    foreach ($queries as $id => $sql) {
        $endpoints["query_$id"] = "api/$id";
    }

    $payload = [
        "message" => "Welcome to the Catalague API",
        "endpoints" => $endpoints
    ];

    $response->getBody()->write(json_encode($payload, JSON_PRETTY_PRINT));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});

$app->get('/api/{id}', function (Request $request, Response $response, array $args) use ($pdo, $queries) {
    $id = (int) $args['id'];

    if(!isset($queries[$id])) {
        $errorPayload = [
            "success" => false, 
            "error" => "Query not found. Please insert an ID between 1 and 10"
        ];

        $response->getBody()->write(json_encode($payload, JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    $sql = $queries[$id];

    try {
        $stmt = $pdo->query($sql);
        $result = [
            "success" => true,
            "data" => $stmt->fetchAll()
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



