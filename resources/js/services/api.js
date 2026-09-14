import axios from 'axios'


export const initCsrf = () => {
    return axios.get('/sanctum/csrf-cookie', {
        withCredentials: true,
    });
}

const api = axios.create({
    baseURL: '/api',
    withCredentials: true,
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

export default api;