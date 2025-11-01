import {defineStore} from 'pinia';
import { useApiUrl } from '../../../api/api';
import { useStates } from '../../states';
import axios from 'axios';
import { toRefs } from 'vue';

const { api, getAuthHeader } = useApiUrl();

export const useAdminProfileStore = defineStore('profile', () => {
    const states = useStates();
    const { user } = toRefs(states);
    const { success, error } = states;

    const showAdminProfile = async () => {
        try {
            const response = await axios.get(`${api}/profile/`, getAuthHeader());
            user.value = response.data.user || {};
            console.log('Profile data:', response.data.user);
        } catch (err) {
            console.error('Error fetching profile:', err);
            error('Failed to fetch profile');
        }
    }

    const updateAdminProfile = async (id, data) => {
        try {
            const response = await axios.put(`${api}/profile`, data, getAuthHeader());
            success(response.data.message || 'Profile updated successfully!');
            console.log('Updated profile data:', response.data);
        } catch (err) {
            console.error('Error updating profile:', err);
            error('Failed to update profile');
        }
    }
    const changeAdminPassword = async (id, data) => {
        try {
            const response = await axios.put(`${api}/profile/password`, data, getAuthHeader());
            success(response.data.message || 'Password changed successfully!');
            console.log('Password change response:', response.data);
        } catch (err) {
            console.error('Error changing password:', err);
            error('Failed to change password');
        }
    }

        return {
        showAdminProfile,
        updateAdminProfile,
        changeAdminPassword
        }
});