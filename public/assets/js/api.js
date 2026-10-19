// Cung origin voi trang web; dung chung cho cac module sau.
const API_BASE = '/api/v1';

async function apiFetch(path, options) {
    if (!options) {
        options = {};
    }

    if (!options.headers) {
        options.headers = {};
    }

    if (options.body && !options.headers['Content-Type']) {
        options.headers['Content-Type'] = 'application/json';
    }

    const token = sessionStorage.getItem('token');
    if (token) {
        options.headers['Authorization'] = 'Bearer ' + token;
    }

    const response = await fetch(API_BASE + path, options);
    let result = null;

    if (response.status !== 204) {
        try {
            result = await response.json();
        } catch (parseError) {
            if (response.status === 401) {
                sessionStorage.removeItem('token');
                if (path !== '/auth/login') {
                    window.location.replace('/login.html');
                }
            }
            const error = new Error('Máy chủ trả dữ liệu không đúng định dạng JSON. Hãy kiểm tra Terminal PHP.');
            error.status = response.status;
            throw error;
        }
    }

    if (!response.ok) {
        if (response.status === 401) {
            sessionStorage.removeItem('token');
            if (path !== '/auth/login') {
                window.location.replace('/login.html');
            }
        }

        let message = 'Không thực hiện được yêu cầu.';
        let fields = {};

        if (result && result.error) {
            message = result.error.message;
            fields = result.error.fields;
        }

        const error = new Error(message);
        error.status = response.status;
        error.fields = fields;
        throw error;
    }

    return result;
}
