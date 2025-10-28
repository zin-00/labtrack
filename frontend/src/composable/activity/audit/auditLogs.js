import { defineStore } from "pinia";
import { useApiUrl } from "../../../api/api";
import { useStates } from "../../states";
import { toRefs } from "vue";

export const useAuditLogsStore = defineStore('auditLogs', () => {
    const { api, getAuthHeader } = useApiUrl();
    const states = useStates();

    const {
        isLoading,
        auditLogs,
        pagination,
        } = toRefs(states)

    const {
        success,
        error
        } = states;

    const getAuditLogs = async (page = 1) => {
        try {
            isLoading.value = true;

            const response  = await axios.get(`${api}/audit-logs?page=${page}`, getAuthHeader());
            auditLogs.value = response.data.audit_logs.data || [];
            pagination.value = {
                current_page: response.data.audit_logs.current_page,
                last_page: response.data.audit_logs.last_page,
                per_page: response.data.audit_logs.per_page,
                total: response.data.audit_logs.total
            }
            console.log('Audit Logs data:', auditLogs.value);
            console.log('Pagination data:', pagination.value);
            success(response.data.message || 'Audit logs fetched successfully!');
        } catch (err) {
            console.error('Error fetching audit logs:', err);
            auditLogs.value = [];
            error(err.response.data.message || 'Failed to fetch audit logs');
        } finally {
            isLoading.value = false;
        }
    }

    return {
        getAuditLogs,
    }

});