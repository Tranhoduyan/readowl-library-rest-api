const loginForm = document.getElementById('login-form');
const loginButton = document.getElementById('login-button');
const message = document.getElementById('message');

loginForm.addEventListener('submit', async function (event) {
    event.preventDefault();
    message.textContent = '';
    message.className = 'message';
    document.getElementById('email-error').textContent = '';
    document.getElementById('password-error').textContent = '';

    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    loginButton.disabled = true;
    loginButton.textContent = 'Đang đăng nhập…';

    try {
        const result = await apiFetch('/auth/login', {
            method: 'POST',
            body: JSON.stringify({ email: email, password: password })
        });
        sessionStorage.setItem('token', result.data.access_token);
        const loggedUser = result.data.user || {};
        if (loggedUser.role === 'admin') {
            window.location.href = 'admin/users.html';
        } else {
            window.location.href = 'my-loans.html';
        }
    } catch (error) {
        message.className = 'message error';
        message.textContent = error.status
            ? error.message
            : 'Không kết nối được máy chủ. Hãy kiểm tra server và thử lại.';
        if (error.fields) {
            document.getElementById('email-error').textContent = error.fields.email || '';
            document.getElementById('password-error').textContent = error.fields.password || '';
        }
    } finally {
        loginButton.disabled = false;
        loginButton.textContent = 'Đăng nhập';
    }
});

