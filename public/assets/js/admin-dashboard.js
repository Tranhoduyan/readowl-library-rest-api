const appWrapper = document.getElementById('app-wrapper');
const btnEdgeToggle = document.getElementById('btn-edge-toggle');
const edgeToggleIcon = document.getElementById('edge-toggle-icon');
const btnHamburgerToggle = document.getElementById('btn-hamburger-toggle');
const statusBanner = document.getElementById('status-banner');

function toggleSidebar() {
    const collapsed = appWrapper.classList.toggle('sidebar-collapsed');
    if (edgeToggleIcon) {
        edgeToggleIcon.className = collapsed ? 'bi bi-chevron-right' : 'bi bi-chevron-left';
    }
}

if (btnEdgeToggle) {
    btnEdgeToggle.addEventListener('click', toggleSidebar);
}
if (btnHamburgerToggle) {
    btnHamburgerToggle.addEventListener('click', toggleSidebar);
}

function handleLogout() {
    sessionStorage.removeItem('token');
    window.location.replace('/login.html');
}

document.getElementById('btn-sidebar-logout').addEventListener('click', handleLogout);
document.getElementById('btn-topbar-logout').addEventListener('click', handleLogout);

async function loadDashboard() {
    if (!sessionStorage.getItem('token')) {
        window.location.replace('/login.html');
        return;
    }

    try {
        const me = await apiFetch('/auth/me');
        const user = me.data;
        if (user.role !== 'admin') {
            window.location.replace('/my-loans.html');
            return;
        }

        document.getElementById('sidebar-user-name').textContent = user.full_name;
        document.getElementById('topbar-user-name').textContent = user.full_name;
        document.getElementById('topbar-avatar-text').textContent = user.full_name
            ? user.full_name.trim().charAt(0).toUpperCase()
            : 'A';

        const statsRes = await apiFetch('/statistics');
        const d = statsRes.data;

        document.getElementById('kpi-members').textContent = d.users.total_members;
        document.getElementById('kpi-books').textContent =
            d.books.available_copies + ' / ' + d.books.total_copies;
        document.getElementById('kpi-active-loans').textContent =
            d.loans.pending_loans + ' chờ / ' + d.loans.borrowed_loans + ' mượn';
        document.getElementById('kpi-overdue').textContent = d.loans.overdue_loans;

        const tbody = document.getElementById('top-books-tbody');
        tbody.textContent = '';
        const topBooks = d.top_books || [];

        if (topBooks.length === 0) {
            const tr = document.createElement('tr');
            const td = document.createElement('td');
            td.colSpan = 6;
            td.className = 'text-center py-4 text-secondary';
            td.textContent = 'Chưa có dữ liệu sách.';
            tr.appendChild(td);
            tbody.appendChild(tr);
            return;
        }

        topBooks.forEach(function (b) {
            const tr = document.createElement('tr');

            const tdId = document.createElement('td');
            tdId.className = 'fw-bold text-secondary';
            tdId.textContent = '#' + b.id;
            tr.appendChild(tdId);

            const tdTitle = document.createElement('td');
            tdTitle.className = 'fw-bold';
            tdTitle.textContent = b.title;
            tr.appendChild(tdTitle);

            const tdAuthor = document.createElement('td');
            tdAuthor.textContent = b.author;
            tr.appendChild(tdAuthor);

            const tdCat = document.createElement('td');
            tdCat.textContent = b.category_name;
            tr.appendChild(tdCat);

            const tdStock = document.createElement('td');
            tdStock.textContent = b.available_copies + ' / ' + b.total_copies;
            tr.appendChild(tdStock);

            const tdCount = document.createElement('td');
            const badge = document.createElement('span');
            badge.className = 'badge text-bg-primary';
            badge.textContent = b.borrow_count + ' lượt';
            tdCount.appendChild(badge);
            tr.appendChild(tdCount);

            tbody.appendChild(tr);
        });
    } catch (error) {
        if (error.status === 401) {
            window.location.replace('/login.html');
            return;
        }
        statusBanner.className = 'alert alert-danger mb-3';
        statusBanner.textContent = error.message || 'Không tải được dữ liệu thống kê.';
    }
}

document.getElementById('btn-refresh').addEventListener('click', loadDashboard);
loadDashboard();
