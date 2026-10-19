const form = document.getElementById('register-form');
const button = document.getElementById('register-button');
const message = document.getElementById('message');

form.addEventListener('submit', async function (event) {
    event.preventDefault();

    message.textContent = '';
    message.className = 'message';
    document.getElementById('full-name-error').textContent = '';
    document.getElementById('email-error').textContent = '';
    document.getElementById('password-error').textContent = '';
    document.getElementById('confirm-password-error').textContent = '';

    const fullName = document.getElementById('full-name').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm-password').value;

    if (password !== confirmPassword) {
        document.getElementById('confirm-password-error').textContent = 'Mật khẩu nhập lại chưa khớp.';
        return;
    }

    button.disabled = true;
    button.textContent = 'Đang đăng ký...';

    try {
        const result = await apiFetch('/auth/register', {
            method: 'POST',
            body: JSON.stringify({
                full_name: fullName,
                email: email,
                password: password
            })
        });

        message.textContent = result.message;
        message.className = 'message success';
        form.reset();
    } catch (error) {
        message.className = 'message error';

        if (!error.status) {
            message.textContent = 'Không gửi được yêu cầu. Vui lòng thử lại.';
        } else {
            message.textContent = error.message;
        }

        if (error.fields) {
            if (error.fields.full_name) {
                document.getElementById('full-name-error').textContent = error.fields.full_name;
            }
            if (error.fields.email) {
                document.getElementById('email-error').textContent = error.fields.email;
            }
            if (error.fields.password) {
                document.getElementById('password-error').textContent = error.fields.password;
            }
        }
    } finally {
        button.disabled = false;
        button.textContent = 'Đăng ký';
    }
});
