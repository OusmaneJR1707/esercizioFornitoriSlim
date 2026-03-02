const API_BASE_URL = 'http://localhost/esercizioFornitoriSlim/backend/api';
const DEFAULT_LIMIT = 10;

document.addEventListener('DOMContentLoaded', () => {
    loadData(1);
});

async function loadData(page = 1) {
    const querySelect = document.getElementById('querySelect');
    const loader = document.getElementById('loader');

    if(!querySelect) return;

    const queryId = querySelect.value;
    loader.style.display = 'block';

    try{
        const response = await fetch(`${API_BASE_URL}/${queryId}?page=${page}&limit=${DEFAULT_LIMIT}`);
        const result = await response.json();

        if(result.success) {
            renderTable(result.data);
            renderPagination(result.pagination);
        } else {
            alert ("API error: " + result.error);
        }
    } catch (error) {
        console.log(error);
        console.error("Connection failed");
    } finally {
        loader.style.display = 'none';
    }
}

function renderTable(data) {
    const header = document.getElementById('tableHeader');
    const body = document.getElementById('tableBody');
    header.innerHTML = '';
    body.innerHTML = '';

    if (!data || data.length === 0) {
        body.innerHTML = '<tr><td class="text-center py-4">Nessun dato trovato.</td></tr>';
        return;
    }

    const dictionary = {
        'pnome': 'Nome Pezzo',
        'fnome': 'Nome Fornitore',
        'fid': 'ID Fornitore',
        'pid': 'ID Pezzo',
        'colore': 'Colore',
        'costo': 'Prezzo',
        'indirizzo': 'Sede Fornitore'
    };

    const keys = Object.keys(data[0]);

    let headerHtml = '<tr>';
    keys.forEach(key => {
        const label = dictionary[key] || key.toUpperCase();
        headerHtml += `<th>${label}</th>`;
    });
    header.innerHTML = headerHtml + '</tr>';

    data.forEach(row => {
        let tr = '<tr>';
        keys.forEach(key => {
            let value = row[key];

            if (key === 'costo' && value !== null) {
                value = `€ ${parseFloat(value).toFixed(2)}`;
            }
            
            tr += `<td>${value}</td>`;
        });
        body.innerHTML += tr + '</tr>';
    });
}

function renderPagination(p) {
    const controls = document.getElementById('paginationControls');
    const info = document.getElementById('pageInfo');

    if (!p.total_records || p.total_records === 0) {
        console.log(p.total_records)
        controls.innerHTML = '';
        info.innerText = 'Nessun risultato trovato';
        return;
    }

    let total_pages = Math.max(1, Math.ceil(p.total_records / DEFAULT_LIMIT));

    info.innerText = `Pagina ${p.current_page} di ${total_pages} (${p.total_records} record)`;

    let html = `
        <li class="page-item ${p.current_page === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="event.preventDefault(); loadData(${p.current_page - 1})">Precedente</a>
        </li>
    `;

    for (let i = 1; i <= total_pages; i++) {
        html += `
            <li class="page-item ${i === p.current_page ? 'active' : ''}">
                <a class="page-link" href="#" onclick="event.preventDefault(); loadData(${i})">${i}</a>
            </li>
        `;
    }

    html += `
        <li class="page-item ${p.current_page === total_pages ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="event.preventDefault(); loadData(${p.current_page + 1})">Successiva</a>
        </li>
    `;

    controls.innerHTML = html;
}

