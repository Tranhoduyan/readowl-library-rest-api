const appWrapper = document.getElementById('app-wrapper');
const btnEdgeToggle = document.getElementById('btn-edge-toggle');
const edgeToggleIcon = document.getElementById('edge-toggle-icon');
const btnHamburgerToggle = document.getElementById('btn-hamburger-toggle');
const statusBanner = document.getElementById('status-banner');
const categoriesTableBody = document.getElementById('categories-table-body');

let catModalInstance = null;
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

async function loadCategories() {
    const res = await apiFetch('/categories');
    allCategories = res.data || [];
    renderCategoriesTable();
}

function renderCategoriesTable() {
    categoriesTableBody.textContent = '';
    if (allCategories.length === 0) {
        const tr = document.createElement('tr');
        const td = document.createElement('td');
        td.colSpan = 5;
        td.className = 'text-center py-4 text-secondary';
        td.textContent = 'Chưa có danh mục sách nào.';
        tr.appendChild(td);
        categoriesTableBody.appendChild(tr);
        return;
    }

    allCategories.forEach(function (c) {
        const tr = document.createElement('tr');

        const tdId = document.createElement('td');
        tdId.className = 'fw-bold text-secondary';
        tdId.textContent = '#' + c.id;
        tr.appendChild(tdId);

        const tdName = document.createElement('td');
        tdName.className = 'fw-bold text-dark';
        tdName.textContent = c.name;
        tr.appendChild(tdName);

        const tdDesc = document.createElement('td');
        tdDesc.className = 'text-secondary small';
        tdDesc.textContent = c.description || '—';
        tr.appendChild(tdDesc);

        const tdCount = document.createElement('td');
        const badge = document.createElement('span');
        badge.className = 'badge rounded-pill bg-primary-subtle text-primary';
        badge.textContent = (c.books_count || 0) + ' đầu sách';
        tdCount.appendChild(badge);
        tr.appendChild(tdCount);

        const tdAct = document.createElement('td');
        tdAct.className = 'text-end';
        const grp = document.createElement('div');
        grp.className = 'd-inline-flex gap-1';

        const btnEdit = document.createElement('button');
        btnEdit.type = 'button';
        btnEdit.className = 'btn btn-sm btn-outline-primary';
        btnEdit.textContent = 'Sửa';
        btnEdit.addEventListener('click', function () {
            openCategoryModal(c);
        });

        const btnDel = document.createElement('button');
        btnDel.type = 'button';
        btnDel.className = 'btn btn-sm btn-outline-danger';
        btnDel.textContent = 'Xóa';
        btnDel.addEventListener('click', function () {
            deleteCategory(c);
        });

        grp.appendChild(btnEdit);
        grp.appendChild(btnDel);
        tdAct.appendChild(grp);
        tr.appendChild(tdAct);

        categoriesTableBody.appendChild(tr);
    });
}

function openCategoryModal(cat) {
    document.getElementById('cat-modal-title').textContent = cat ? ('Chỉnh sửa Danh mục #' + cat.id) : 'Thêm danh mục mới';
    document.getElementById('cat-id-input').value = cat ? String(cat.id) : '';
    document.getElementById('cat-name-input').value = cat ? cat.name : '';
    document.getElementById('cat-desc-input').value = cat && cat.description ? cat.description : '';

    if (!catModalInstance) {
        catModalInstance = new bootstrap.Modal(document.getElementById('categoryFormModal'));
    }
    catModalInstance.show();
}

document.getElementById('btn-add-category').addEventListener('click', function () {
    openCategoryModal(null);
});

document.getElementById('category-form').addEventListener('submit', async function (event) {
    event.preventDefault();
    const id = document.getElementById('cat-id-input').value;
    const payload = {
        name: document.getElementById('cat-name-input').value.trim(),
        description: document.getElementById('cat-desc-input').value.trim()
    };

    try {
        if (id) {
            const res = await apiFetch('/categories/' + encodeURIComponent(id), {
                method: 'PUT',
                body: JSON.stringify(payload)
            });
            showBanner('success', res.message || 'Cập nhật danh mục thành công.');
        } else {
            const res = await apiFetch('/categories', {
                method: 'POST',
                body: JSON.stringify(payload)
            });
            showBanner('success', res.message || 'Thêm danh mục thành công.');
        }
        if (catModalInstance) {
            catModalInstance.hide();
        }
        await loadCategories();
    } catch (error) {
        showBanner('error', error.message || 'Không lưu được danh mục.');
    }
});

async function deleteCategory(cat) {
    if (!window.confirm('Bạn có chắc chắn muốn xóa danh mục "' + cat.name + '" (#' + cat.id + ')?')) {
        return;
    }
    try {
        await apiFetch('/categories/' + encodeURIComponent(cat.id), { method: 'DELETE' });
        showBanner('success', 'Đã xóa danh mục "' + cat.name + '" thành công.');
        await loadCategories();
    } catch (error) {
        showBanner('error', error.message || 'Không thể xóa danh mục.');
    }
}

async function initCategoriesPage() {
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
        await loadCategories();
    } catch (e) {
        window.location.replace('/login.html');
    }
}

initCategoriesPage();
