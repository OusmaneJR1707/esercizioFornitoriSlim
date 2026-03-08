<?php
// Protezione della rotta
if (!isset($_SESSION['user'])) {
    header("Location: /esercizioFornitoriSlim/frontend/login");
    exit;
}
$user = $_SESSION['user'];
$isAdmin = $user['ruolo'] === 'admin';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SupplySystem</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', system-ui, sans-serif; background-color: #f0f2f5; overflow-x: hidden; }
        .wrapper { display: flex; width: 100%; height: 100vh; }
        .sidebar { width: 250px; background-color: #001529; color: #fff; flex-shrink: 0; display: flex; flex-direction: column; }
        .sidebar-header { padding: 20px; font-size: 1.25rem; font-weight: bold; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-menu { padding: 10px 0; list-style: none; margin: 0; flex-grow: 1; }
        .sidebar-menu li a { display: block; padding: 12px 20px; color: #a6adb4; text-decoration: none; transition: 0.2s; cursor: pointer; }
        .sidebar-menu li a:hover, .sidebar-menu li a.active { background-color: #1677ff; color: #fff; }
        .sidebar-menu li a i { margin-right: 10px; width: 20px; text-align: center; }
        .main-panel { flex-grow: 1; display: flex; flex-direction: column; height: 100vh; overflow-y: auto; }
        .top-navbar { background-color: #fff; min-height: 60px; display: flex; align-items: center; justify-content: space-between; padding: 0 24px; box-shadow: 0 1px 4px rgba(0,21,41,.08); z-index: 10; }
        .content-body { padding: 24px; flex-grow: 1; }
        .product-card { border: none; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); transition: transform 0.2s, box-shadow 0.2s; }
        .product-card:hover { transform: translateY(-3px); box-shadow: 0 8px 15px rgba(0,0,0,0.1); }
        .admin-table-card { background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); overflow: hidden; }
        .table th { font-weight: 600; color: #475569; background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; }
        .table td { vertical-align: middle; }
    </style>
</head>
<body>

<div class="wrapper">
    <nav class="sidebar">
        <div class="sidebar-header">
            <i class="bi bi-box-seam me-2"></i> SupplySystem
        </div>
        <ul class="sidebar-menu" id="sidebarMenu">
            <?php if ($isAdmin): ?>
                <li><a class="active" onclick="loadAdminView('overview', this)"><i class="bi bi-grid-1x2"></i> Catalogo Globale</a></li>
                <li><a onclick="loadAdminView('fornitori', this)"><i class="bi bi-buildings"></i> Fornitori</a></li>
                <li><a onclick="loadAdminView('pezzi', this)"><i class="bi bi-nut"></i> Anagrafica Pezzi</a></li>
            <?php else: ?>
                <li><a class="active" onclick="loadSupplierDashboard(this)"><i class="bi bi-shop"></i> I Miei Prodotti</a></li>
                <li><a onclick="openProfileModal()"><i class="bi bi-person-lines-fill"></i> Il Mio Profilo</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="main-panel">
        <header class="top-navbar">
            <h5 class="mb-0 text-dark fw-bold" id="pageTitle"><?= $isAdmin ? 'Catalogo Globale' : 'I Miei Prodotti' ?></h5>
            <div class="d-flex align-items-center">
                <span class="me-3 text-muted">Bentornato, <strong class="text-dark"><?= htmlspecialchars($user['username']) ?></strong></span>
                <a href="/esercizioFornitoriSlim/frontend/logout" class="btn btn-sm btn-outline-danger"><i class="bi bi-box-arrow-right"></i> Esci</a>
            </div>
        </header>

        <main class="content-body" id="dynamicContent">
            </main>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content text-center p-4">
            <i class="bi bi-exclamation-triangle text-danger mb-3" style="font-size: 3rem;"></i>
            <h5>Sei sicuro?</h5>
            <p class="text-muted small">L'azione è irreversibile.</p>
            <input type="hidden" id="delType">
            <input type="hidden" id="delId1">
            <input type="hidden" id="delId2">
            <div class="mt-3">
                <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-danger" onclick="executeDelete()">Sì, Elimina</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="profileModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Il Mio Profilo Aziendale</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label text-muted small">Nome Azienda</label>
                    <input type="text" class="form-control" id="profNome">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small">Indirizzo Sede</label>
                    <input type="text" class="form-control" id="profIndirizzo">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success w-100" onclick="submitProfile()">Salva Modifiche</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Aggiungi Prodotto al Catalogo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label text-muted small">Aggiungi pezzo esistente o creane uno nuovo?</label>
                    <select class="form-select border-primary" id="insertType" onchange="toggleNewProductFields()">
                        <option value="existing">Scegli dal Database Centrale</option>
                        <option value="new">Crea un Nuovo Pezzo da zero</option>
                    </select>
                </div>

                <div class="mb-3" id="existingPieceSection">
                    <label class="form-label text-muted small">Scegli il Pezzo</label>
                    <select class="form-select" id="selectPezzo"></select>
                </div>

                <div id="newPieceSection" style="display: none; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 15px;">
                    <h6 class="text-primary mb-3"><i class="bi bi-box"></i> Dati Nuovo Pezzo</h6>
                    <input type="text" id="newPnome" class="form-control mb-2" placeholder="Nome Pezzo (es. Staffa in Ferro)">
                    <input type="text" id="newColore" class="form-control" placeholder="Colore">
                </div>
                <hr>
                <div class="mb-3">
                    <label class="form-label text-primary fw-bold small">Il Tuo Prezzo di Vendita (€)</label>
                    <input type="number" step="0.01" class="form-control border-primary" id="inputCosto" placeholder="es. 15.50">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary w-100" onclick="submitNewProduct()">Salva nel mio catalogo</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="adminModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="adminModalTitle">Gestione</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="adminModalBody">
                </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary w-100" onclick="submitAdminForm()">Salva</button>
            </div>
        </div>
    </div>
</div>

<script>
    const currentUser = <?= json_encode($user) ?>;
    function toggleNewProductFields() {
        const type = document.getElementById('insertType').value;
        document.getElementById('existingPieceSection').style.display = type === 'new' ? 'none' : 'block';
        document.getElementById('newPieceSection').style.display = type === 'new' ? 'block' : 'none';
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/dashboard.js"></script>
</body>
</html>