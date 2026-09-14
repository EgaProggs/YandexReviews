import api from './api'

export const getReviews = (organizationId, page = 1) => {
    return api.get(`/organizations/${organizationId}/reviews`, {
        params: {
            page,
            page_size: 50,
        },
    })
}