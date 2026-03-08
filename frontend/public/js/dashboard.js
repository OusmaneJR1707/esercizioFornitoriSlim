const API_BASE_URL = '/esercizioFornitoriSlim/backend/api';
const contentDiv = document.getElementById('dynamicContent');
const pageTitle = document.getElementById('pageTitle');

let currentAdminView = 'overview';
let currentAdminData = []; 

document.addEventListener('DOMContentLoaded', () => {
    if (currentUser.ruolo === 'fornitore') {
        loadSupplierDashboard();
    } else {
        loadAdminView('overview');
    }
});

function updateSidebar(element) {
    if(!element) return;
    document.querySelectorAll('.sidebar-menu a').forEach(el => el.classList.remove('active'));
    element.classList.add('active');
}

// ==========================================
// SEZIONE FORNITORE
// ==========================================

async function loadSupplierDashboard(element = null) {
    if(element) updateSidebar(element);
    contentDiv.innerHTML = '<div class="text-center mt-5"><div class="spinner-border text-primary"></div></div>';
    try {
        const response = await fetch(`${API_BASE_URL}/catalogo?fid=${currentUser.fid}`);
        const result = await response.json();
        if (result.success) renderSupplierCards(result.data);
    } catch (e) {
        contentDiv.innerHTML = `<div class="alert alert-danger">Errore API.</div>`;
    }
}

function renderSupplierCards(data) {
    let html = `
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h6 class="text-muted m-0">Hai ${data.length} prodotti in vendita</h6>
            <button class="btn btn-primary shadow-sm" onclick="openAddProductModal()">
                <i class="bi bi-plus-lg me-1"></i> Aggiungi Prodotto
            </button>
        </div>
        <div class="row g-4">
    `;

    if (data.length === 0) {
        html += `<div class="col-12"><div class="alert alert-info border-0 shadow-sm">Il tuo catalogo è vuoto.</div></div>`;
    } else {
        data.forEach(item => {
            html += `
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <div class="card product-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3">
                                <span class="badge bg-light text-dark border">ID: ${item.pid}</span>
                                <span class="badge bg-secondary opacity-75">${item.colore || 'N/D'}</span>
                            </div>
                            <h5 class="card-title text-dark fw-bold text-truncate" title="${item.pnome}">${item.pnome}</h5>
                            <h2 class="text-primary mt-3 mb-0" id="price-${item.pid}">€ ${parseFloat(item.costo).toFixed(2)}</h2>
                        </div>
                        <div class="card-footer bg-transparent border-top-0 d-flex gap-2 pb-3 pt-0">
                            <button class="btn btn-light flex-grow-1 text-primary border" onclick="editPrice('${currentUser.fid}', '${item.pid}', ${item.costo})">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <button class="btn btn-light text-danger border" onclick="prepareDelete('catalogo', '${currentUser.fid}', '${item.pid}')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
    }
    html += `</div>`;
    contentDiv.innerHTML = html;
}

// ==========================================
// FUNZIONI CONDIVISE (PREZZO E PROFILO)
// ==========================================

async function editPrice(fid, pid, currentPrice) {
    const newPrice = prompt(`Inserisci il nuovo prezzo:`, currentPrice);
    if (newPrice !== null && newPrice.trim() !== "" && !isNaN(newPrice)) {
        try {
            const response = await fetch(`${API_BASE_URL}/catalogo/${fid}/${pid}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ costo: parseFloat(newPrice) })
            });
            const result = await response.json();
            if (result.success) {
                const priceEl = document.getElementById(`price-${pid}`);
                if(priceEl) {
                    priceEl.innerText = `€ ${parseFloat(newPrice).toFixed(2)}`;
                    priceEl.classList.add('text-success');
                    setTimeout(() => priceEl.classList.remove('text-success'), 1500);
                } else {
                    loadAdminView('overview');
                }
            } else { alert("Errore: " + result.error); }
        } catch (e) { alert("Errore di rete."); }
    }
}

async function openProfileModal() {
    try {
        const response = await fetch(`${API_BASE_URL}/supplier/${currentUser.fid}`);
        const result = await response.json();
        if (result.success) {
            document.getElementById('profNome').value = result.data.fnome;
            document.getElementById('profIndirizzo').value = result.data.indirizzo;
            new bootstrap.Modal(document.getElementById('profileModal')).show();
        }
    } catch (e) { alert("Errore caricamento."); }
}

async function submitProfile() {
    const fnome = document.getElementById('profNome').value;
    const indirizzo = document.getElementById('profIndirizzo').value;
    try {
        const response = await fetch(`${API_BASE_URL}/supplier/${currentUser.fid}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ fnome, indirizzo })
        });
        const result = await response.json();
        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('profileModal')).hide();
            alert("Profilo aggiornato!");
        } else { alert("Errore server: " + result.error); }
    } catch (e) { alert("Errore di rete"); }
}

async function openAddProductModal() {
    try {
        const response = await fetch(`${API_BASE_URL}/product`);
        const result = await response.json();
        if (result.success) {
            const select = document.getElementById('selectPezzo');
            select.innerHTML = '<option value="">-- Seleziona un pezzo --</option>';
            result.data.forEach(p => select.innerHTML += `<option value="${p.pid}">#${p.pid} - ${p.pnome} (${p.colore})</option>`);
            
            document.getElementById('inputCosto').value = '';
            document.getElementById('insertType').value = 'existing';
            toggleNewProductFields(); 
            new bootstrap.Modal(document.getElementById('addProductModal')).show();
        }
    } catch (e) { alert("Impossibile caricare i pezzi."); }
}

async function submitNewProduct() {
    const insertType = document.getElementById('insertType').value;
    const costo = document.getElementById('inputCosto').value;
    if (!costo) return alert("Inserisci il prezzo di vendita!");

    let pidToUse = '';

    if (insertType === 'new') {
        const newPnome = document.getElementById('newPnome').value;
        const newColore = document.getElementById('newColore').value;
        if (!newPnome) return alert("Compila il Nome per il pezzo.");

        try {
            // Niente ID inviato, lo crea il backend
            const resPezzo = await fetch(`${API_BASE_URL}/product`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ pnome: newPnome, colore: newColore }) 
            });
            const resultPezzo = await resPezzo.json();
            
            if (!resultPezzo.success) return alert("Errore creazione pezzo: " + resultPezzo.error);
            
            // Il backend deve ritornare il pid generato (es: {success: true, pid: 15})
            pidToUse = resultPezzo.pid; 
            if(!pidToUse) return alert("Errore: L'API non ha restituito il nuovo ID!");
            
        } catch (e) { return alert("Errore di rete."); }
    } else {
        pidToUse = document.getElementById('selectPezzo').value;
        if (!pidToUse) return alert("Seleziona un pezzo.");
    }

    // Aggiunge al catalogo
    try {
        const response = await fetch(`${API_BASE_URL}/catalogo`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ fid: currentUser.fid, pid: pidToUse, costo: costo })
        });
        const result = await response.json();
        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('addProductModal')).hide();
            loadSupplierDashboard(); 
        } else { alert("Errore: Pezzo già nel catalogo?"); }
    } catch (e) { alert("Errore di rete"); }
}

// ==========================================
// SEZIONE ADMIN (DASHBOARD ROUTER)
// ==========================================

async function loadAdminView(view, element = null) {
    if(element) updateSidebar(element);
    currentAdminView = view;
    contentDiv.innerHTML = '<div class="text-center mt-5"><div class="spinner-border text-primary"></div></div>';

    try {
        let res, data;
        if (view === 'overview') {
            pageTitle.innerText = "Catalogo Globale (Tutti i prezzi)";
            res = await fetch(`${API_BASE_URL}/catalogo`);
            data = await res.json();
            currentAdminData = data.data;
            renderAdminTable(currentAdminData, ['FID', 'Fornitore', 'PID', 'Pezzo', 'Costo', 'Azioni'], 'Nessun dato nel catalogo.', 'catalogo');
        } 
        else if (view === 'fornitori') {
            pageTitle.innerText = "Anagrafica Fornitori";
            res = await fetch(`${API_BASE_URL}/supplier`);
            data = await res.json();
            currentAdminData = data.data;
            renderAdminTable(currentAdminData, ['FID', 'Nome Azienda', 'Indirizzo', 'Azioni'], 'Nessun fornitore.', 'supplier');
        } 
        else if (view === 'pezzi') {
            pageTitle.innerText = "Anagrafica Pezzi Database";
            res = await fetch(`${API_BASE_URL}/product`);
            data = await res.json();
            currentAdminData = data.data;
            renderAdminTable(currentAdminData, ['PID', 'Nome Pezzo', 'Colore', 'Azioni'], 'Nessun pezzo.', 'product');
        }
    } catch (e) { contentDiv.innerHTML = `<div class="alert alert-danger">Errore di caricamento dati.</div>`; }
}

function renderAdminTable(data, headers, emptyMsg, type) {
    let html = '';
    
    if (type === 'supplier') html += `<div class="text-end mb-3"><button class="btn btn-primary" onclick="openAdminModal('supplier', 'create')"><i class="bi bi-plus-lg"></i> Nuovo Fornitore</button></div>`;
    else if (type === 'product') html += `<div class="text-end mb-3"><button class="btn btn-primary" onclick="openAdminModal('product', 'create')"><i class="bi bi-plus-lg"></i> Nuovo Pezzo</button></div>`;
    else if (type === 'catalogo') html += `<div class="text-end mb-3"><button class="btn btn-primary" onclick="openAdminModal('catalogo', 'create')"><i class="bi bi-plus-lg"></i> Aggiungi al Catalogo</button></div>`;

    html += `<div class="admin-table-card"><div class="table-responsive"><table class="table table-hover mb-0"><thead class="table-light"><tr>`;
    headers.forEach(h => html += `<th>${h}</th>`);
    html += `</tr></thead><tbody>`;

    if (data.length === 0) {
        html += `<tr><td colspan="${headers.length}" class="text-center py-4 text-muted">${emptyMsg}</td></tr>`;
    } else {
        data.forEach(row => {
            html += `<tr>`;
            if (type === 'supplier') {
                html += `<td><strong>#${row.fid}</strong></td><td>${row.fnome}</td><td>${row.indirizzo || '-'}</td>`;
                html += `<td>
                            <button class="btn btn-sm btn-outline-primary me-2" onclick="openAdminModal('supplier', 'edit', '${row.fid}')"><i class="bi bi-pencil"></i></button>
                            <button class="btn btn-sm btn-outline-danger" onclick="prepareDelete('supplier', '${row.fid}', null)"><i class="bi bi-trash"></i></button>
                         </td>`;
            } else if (type === 'product') {
                html += `<td><strong>#${row.pid}</strong></td><td>${row.pnome}</td><td><span class="badge bg-secondary">${row.colore}</span></td>`;
                html += `<td>
                            <button class="btn btn-sm btn-outline-primary me-2" onclick="openAdminModal('product', 'edit', '${row.pid}')"><i class="bi bi-pencil"></i></button>
                            <button class="btn btn-sm btn-outline-danger" onclick="prepareDelete('product', '${row.pid}', null)"><i class="bi bi-trash"></i></button>
                         </td>`;
            } else if (type === 'catalogo') {
                html += `<td>#${row.fid}</td><td>${row.fnome}</td><td>#${row.pid}</td><td>${row.pnome}</td><td class="text-primary fw-bold">€ ${parseFloat(row.costo).toFixed(2)}</td>`;
                html += `<td>
                            <button class="btn btn-sm btn-outline-primary me-2" onclick="openAdminModal('catalogo', 'edit', '${row.fid}', '${row.pid}')"><i class="bi bi-pencil"></i></button>
                            <button class="btn btn-sm btn-outline-danger" onclick="prepareDelete('catalogo', '${row.fid}', '${row.pid}')"><i class="bi bi-trash"></i></button>
                         </td>`;
            }
            html += `</tr>`;
        });
    }
    html += `</tbody></table></div></div>`;
    contentDiv.innerHTML = html;
}

// ==========================================
// MODALI ADMIN E AZIONI GLOBALI (CRUD)
// ==========================================

async function openAdminModal(type, action, id1 = null, id2 = null) {
    document.getElementById('delType').value = type + '_' + action; 
    const body = document.getElementById('adminModalBody');
    let item = null;

    if (action === 'edit') {
        if (type === 'catalogo') item = currentAdminData.find(i => i.fid == id1 && i.pid == id2);
        else item = currentAdminData.find(i => (type === 'product' ? i.pid == id1 : i.fid == id1));
    }

    if(type === 'product') {
        document.getElementById('adminModalTitle').innerText = action === 'create' ? "Crea Pezzo" : "Modifica Pezzo";
        // Non mostriamo l'ID in fase di creazione (Auto-increment)
        let idField = action === 'edit' ? `<label class="small text-muted">ID Pezzo</label><input type="text" id="addPid" class="form-control mb-2 bg-light" readonly value="${item.pid}">` : ``;
        body.innerHTML = `
            ${idField}
            <input type="text" id="addPnome" class="form-control mb-2" placeholder="Nome Pezzo" value="${item ? item.pnome : ''}">
            <input type="text" id="addColore" class="form-control" placeholder="Colore" value="${item ? item.colore : ''}">
        `;
        new bootstrap.Modal(document.getElementById('adminModal')).show();
    } 
    else if (type === 'supplier') {
        document.getElementById('adminModalTitle').innerText = action === 'create' ? "Crea Fornitore" : "Modifica Fornitore";
        let idField = action === 'edit' ? `<label class="small text-muted">ID Fornitore</label><input type="text" id="addFid" class="form-control mb-2 bg-light" readonly value="${item.fid}">` : ``;
        body.innerHTML = `
            ${idField}
            <input type="text" id="addFnome" class="form-control mb-2" placeholder="Nome Azienda" value="${item ? item.fnome : ''}">
            <input type="text" id="addIndirizzo" class="form-control" placeholder="Indirizzo" value="${item ? item.indirizzo : ''}">
        `;
        new bootstrap.Modal(document.getElementById('adminModal')).show();
    }
    else if (type === 'catalogo') {
        // L'admin modifica l'intero record (Fornitore e Prodotto a scelta)
        document.getElementById('adminModalTitle').innerText = action === 'create' ? "Aggiungi al Catalogo" : "Modifica Voce Catalogo";
        body.innerHTML = '<div class="text-center"><div class="spinner-border text-primary"></div></div>';
        new bootstrap.Modal(document.getElementById('adminModal')).show();

        // Carica dati per le tendine
        const [resProd, resSupp] = await Promise.all([ fetch(`${API_BASE_URL}/product`), fetch(`${API_BASE_URL}/supplier`) ]);
        const prodData = await resProd.json();
        const suppData = await resSupp.json();

        let suppOpts = '<option value="">-- Seleziona Fornitore --</option>';
        suppData.data.forEach(s => {
            let sel = (item && item.fid == s.fid) ? 'selected' : '';
            suppOpts += `<option value="${s.fid}" ${sel}>#${s.fid} - ${s.fnome}</option>`;
        });

        let prodOpts = '<option value="">-- Seleziona Pezzo --</option>';
        prodData.data.forEach(p => {
            let sel = (item && item.pid == p.pid) ? 'selected' : '';
            prodOpts += `<option value="${p.pid}" ${sel}>#${p.pid} - ${p.pnome}</option>`;
        });

        body.innerHTML = `
            ${action === 'edit' ? `<input type="hidden" id="oldFid" value="${item.fid}"><input type="hidden" id="oldPid" value="${item.pid}">` : ''}
            <label class="form-label small text-muted">Azienda Fornitrice</label>
            <select id="addCatFid" class="form-select mb-2">${suppOpts}</select>
            <label class="form-label small text-muted">Prodotto</label>
            <select id="addCatPid" class="form-select mb-2">${prodOpts}</select>
            <label class="form-label small text-muted">Prezzo Finale (€)</label>
            <input type="number" step="0.01" id="addCatCosto" class="form-control" placeholder="es. 10.50" value="${item ? item.costo : ''}">
        `;
    }
}

async function submitAdminForm() {
    const actionMode = document.getElementById('delType').value; 
    let url = '';
    let method = actionMode.includes('create') ? 'POST' : 'PUT';
    let payload = {};

    if (actionMode.startsWith('product')) {
        payload = { pnome: document.getElementById('addPnome').value, colore: document.getElementById('addColore').value };
        url = actionMode.includes('edit') ? `${API_BASE_URL}/product/${document.getElementById('addPid').value}` : `${API_BASE_URL}/product`;
    } 
    else if (actionMode.startsWith('supplier')) {
        payload = { fnome: document.getElementById('addFnome').value, indirizzo: document.getElementById('addIndirizzo').value };
        url = actionMode.includes('edit') ? `${API_BASE_URL}/supplier/${document.getElementById('addFid').value}` : `${API_BASE_URL}/supplier`;
    }
    else if (actionMode.startsWith('catalogo')) {
        const newFid = document.getElementById('addCatFid').value;
        const newPid = document.getElementById('addCatPid').value;
        const costo = document.getElementById('addCatCosto').value;

        if(!newFid || !newPid || !costo) return alert("Compila tutti i campi!");

        if (actionMode.includes('create')) {
            url = `${API_BASE_URL}/catalogo`;
            method = 'POST';
            payload = { fid: newFid, pid: newPid, costo: costo };
        } else {
            const oldFid = document.getElementById('oldFid').value;
            const oldPid = document.getElementById('oldPid').value;

            // Logica Sicura: se ha cambiato le chiavi primarie, il backend non può fare un semplice PUT. 
            // Eliminiano il vecchio e inseriamo il nuovo.
            if (oldFid !== newFid || oldPid !== newPid) {
                try {
                    await fetch(`${API_BASE_URL}/catalogo/${oldFid}/${oldPid}`, { method: 'DELETE' });
                    url = `${API_BASE_URL}/catalogo`;
                    method = 'POST';
                    payload = { fid: newFid, pid: newPid, costo: costo };
                } catch(e) { return alert("Errore modifica chiavi."); }
            } else {
                url = `${API_BASE_URL}/catalogo/${oldFid}/${oldPid}`;
                method = 'PUT';
                payload = { costo: costo };
            }
        }
    }

    try {
        const response = await fetch(url, { 
            method: method, 
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload) 
        });
        const result = await response.json();
        
        if(result.success) {
            bootstrap.Modal.getInstance(document.getElementById('adminModal')).hide();
            loadAdminView(currentAdminView);
        } else { alert("Errore: " + result.error); }
    } catch (e) { alert("Errore di rete"); }
}

function prepareDelete(type, id1, id2 = null) {
    document.getElementById('delType').value = type;
    document.getElementById('delId1').value = id1;
    document.getElementById('delId2').value = id2;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

async function executeDelete() {
    const type = document.getElementById('delType').value;
    const id1 = document.getElementById('delId1').value;
    const id2 = document.getElementById('delId2').value;
    
    let url = '';
    if (type === 'catalogo') url = `${API_BASE_URL}/catalogo/${id1}/${id2}`;
    else if (type === 'supplier') url = `${API_BASE_URL}/supplier/${id1}`;
    else if (type === 'product') url = `${API_BASE_URL}/product/${id1}`;

    try {
        const response = await fetch(url, { method: 'DELETE' });
        const result = await response.json();
        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
            if (currentUser.ruolo === 'fornitore') loadSupplierDashboard();
            else loadAdminView(currentAdminView);
        } else { alert("Errore: " + result.error); }
    } catch (error) { alert("Errore di rete."); }
}