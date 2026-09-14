import api from './api'

export const getOrganizations = (page = 1, pageSize = 20) => {
    return api.get('/organizations', {
        params: {
            page,
            page_size: pageSize,
        },
    })
}

export const createOrganization = (yandexUrl) => {
    return api.post('/organizations', {
        yandex_url: yandexUrl,
    })
}

export const getOrganization = (id) => {
    return api.get(`/organizations/${id}`)
}

export const parseOrganization = (id) => {
    return api.post(`/organizations/${id}/parse`)
}