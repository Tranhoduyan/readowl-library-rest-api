const appWrapper = document.getElementById('app-wrapper');
const btnEdgeToggle = document.getElementById('btn-edge-toggle');
const edgeToggleIcon = document.getElementById('edge-toggle-icon');
const btnHamburgerToggle = document.getElementById('btn-hamburger-toggle');
const statusBanner = document.getElementById('status-banner');
const booksTableBody = document.getElementById('books-table-body');
const searchInput = document.getElementById('search-input');

let bookModalInstance = null;
let allBooks = [];
let allCategories = [];

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

function showBanner(type, text) {
    statusBanner.className = 'alert ' + (type === 'error' ? 'alert-danger' : 'alert-success') + ' mb-3';
    statusBanner.textContent = text;
}

function handleLogout() {
    sessionStorage.removeItem('token');
    window.location.replace('/login.html');
}

document.getElementById('btn-sidebar-logout').addEventListener('click', handleLogout);
document.getElementById('btn-topbar-logout').addEventListener('click', handleLogout);

async function loadBooks(q) {
    const query = q ? ('&q=' + encodeURIComponent(q)) : '';
    const res = await apiFetch('/books?include_inactive=1' + query);
    allCategories = res.data.categories || [];
    allBooks = res.data.books || [];

    const catSelect = document.getElementById('book-category-input');
    catSelect.textContent = '';
    allCategories.forEach(function (c) {
        const opt = document.createElement('option');
        opt.value = String(c.id);
        opt.textContent = c.name;
        catSelect.appendChild(opt);
    });

    renderBooksTable();
}

function renderBooksTable() {
    booksTableBody.textContent = '';
    if (allBooks.length === 0) {
        const tr = document.createElement('tr');
        const td = document.createElement('td');
        td.colSpan = 6;
        td.className = 'text-center py-4 text-secondary';
        td.textContent = 'Không tìm thấy đầu sách nào.';
        tr.appendChild(td);
        booksTableBody.appendChild(tr);
        return;
    }

    allBooks.forEach(function (b) {
        const tr = document.createElement('tr');
        const isActive = Number(b.is_active) === 1;

        const tdId = document.createElement('td');
        tdId.className = 'fw-bold text-secondary';
        tdId.textContent = '#' + b.id;
        tr.appendChild(tdId);

        const tdInfo = document.createElement('td');
        const tDiv = document.createElement('div');
        tDiv.className = 'fw-bold text-dark';
        tDiv.textContent = b.title;
        const aDiv = document.createElement('div');
        aDiv.className = 'small text-secondary';
        aDiv.textContent = b.author + (b.publication_year ? (' • ' + b.publication_year) : '');
        tdInfo.appendChild(tDiv);
        tdInfo.appendChild(aDiv);
        tr.appendChild(tdInfo);

        const tdCat = document.createElement('td');
        tdCat.textContent = b.category_name;
        tr.appendChild(tdCat);

        const tdStock = document.createElement('td');
        const stockBadge = document.createElement('span');
        stockBadge.className = 'badge ' + (b.available_copies > 0 ? 'text-bg-primary' : 'text-bg-warning');
        stockBadge.textContent = 'Còn ' + b.available_copies + ' / ' + b.total_copies + ' bản';
        tdStock.appendChild(stockBadge);
        tr.appendChild(tdStock);

        const tdSt = document.createElement('td');
        const stBadge = document.createElement('span');
        stBadge.className = 'badge rounded-pill ' + (isActive ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary');
        stBadge.textContent = isActive ? 'Đang cho mượn' : 'Ngừng cho mượn';
        tdSt.appendChild(stBadge);
        tr.appendChild(tdSt);

        const tdAct = document.createElement('td');
        tdAct.className = 'text-end';
        const grp = document.createElement('div');
        grp.className = 'd-inline-flex gap-1';

        const btnEdit = document.createElement('button');
        btnEdit.type = 'button';
        btnEdit.className = 'btn btn-sm btn-outline-primary';
        btnEdit.textContent = 'Sửa';
        btnEdit.addEventListener('click', function () {
            openBookModal(b);
        });

        const btnDel = document.createElement('button');
        btnDel.type = 'button';
        btnDel.className = 'btn btn-sm btn-outline-danger';
        btnDel.textContent = 'Xóa / Ngừng';
        btnDel.addEventListener('click', function () {
            deleteOrDeactivateBook(b.id);
        });

        grp.appendChild(btnEdit);
        grp.appendChild(btnDel);
        tdAct.appendChild(grp);
        tr.appendChild(tdAct);

        booksTableBody.appendChild(tr);
    });
}

function openBookModal(book) {
    document.getElementById('book-modal-title').textContent = book ? ('Chỉnh sửa Sách #' + book.id) : 'Thêm đầu sách mới';
    document.getElementById('book-id-input').value = book ? String(book.id) : '';
    document.getElementById('book-title-input').value = book ? book.title : '';
    document.getElementById('book-author-input').value = book ? book.author : '';
    if (book) {
        document.getElementById('book-category-input').value = String(book.category_id);
    }
    document.getElementById('book-copies-input').value = book ? String(book.total_copies) : '3';
    document.getElementById('book-publisher-input').value = book && book.publisher ? book.publisher : '';
    document.getElementById('book-year-input').value = book && book.publication_year ? String(book.publication_year) : '';
    document.getElementById('book-desc-input').value = book && book.description ? book.description : '';
    document.getElementById('book-active-input').checked = book ? Number(book.is_active) === 1 : true;

    if (!bookModalInstance) {
        bookModalInstance = new bootstrap.Modal(document.getElementById('bookFormModal'));
    }
    bookModalInstance.show();
}

document.getElementById('btn-add-book').addEventListener('click', function () {
    openBookModal(null);
});

document.getElementById('book-form').addEventListener('submit', async function (event) {
    event.preventDefault();
    const id = document.getElementById('book-id-input').value;
    const payload = {
        category_id: Number(document.getElementById('book-category-input').value),
        title: document.getElementById('book-title-input').value.trim(),
        author: document.getElementById('book-author-input').value.trim(),
        total_copies: Number(document.getElementById('book-copies-input').value),
        publisher: document.getElementById('book-publisher-input').value.trim(),
        publication_year: document.getElementById('book-year-input').value || null,
        description: document.getElementById('book-desc-input').value.trim(),
        is_active: document.getElementById('book-active-input').checked ? 1 : 0
    };

    try {
        if (id) {
            const res = await apiFetch('/books/' + encodeURIComponent(id), {
                method: 'PUT',
                body: JSON.stringify(payload)
            });
            showBanner('success', res.message || 'Cập nhật sách thành công.');
        } else {
            const res = await apiFetch('/books', {
                method: 'POST',
                body: JSON.stringify(payload)
            });
            showBanner('success', res.message || 'Thêm sách thành công.');
        }
        if (bookModalInstance) {
            bookModalInstance.hide();
        }
        await loadBooks(searchInput.value.trim());
    } catch (error) {
        showBanner('error', error.message || 'Không lưu được đầu sách.');
    }
});

async function deleteOrDeactivateBook(bookId) {
    try {
        const res = await apiFetch('/books/' + encodeURIComponent(bookId), { method: 'DELETE' });
        showBanner('success', (res && res.message) || 'Đã xóa đầu sách.');
        await loadBooks(searchInput.value.trim());
    } catch (error) {
        showBanner('error', error.message || 'Không thể xóa đầu sách.');
    }
}

document.getElementById('topbar-search-form').addEventListener('submit', function (event) {
    event.preventDefault();
    loadBooks(searchInput.value.trim());
});

async function initBooksPage() {
    if (!sessionStorage.getItem('token')) {
        window.location.replace('/login.html');
        return;
    }
    try {
        const me = await apiFetch('/auth/me');
        if (me.data.role !== 'admin') {
            window.location.replace('/my-loans.html');
            return;
        }
        document.getElementById('sidebar-user-name').textContent = me.data.full_name;
        document.getElementById('topbar-user-name').textContent = me.data.full_name;
        document.getElementById('topbar-avatar-text').textContent = me.data.full_name
            ? me.data.full_name.trim().charAt(0).toUpperCase()
            : 'A';
        await loadBooks('');
    } catch (e) {
        window.location.replace('/login.html');
    }
}

initBooksPage();
