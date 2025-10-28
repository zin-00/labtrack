export const useApiUrl = () => {
  const protocol = window.location.protocol; // 'http:' or 'https:'
  const host = window.location.hostname;
  const port = 8000;

  const api = `${protocol}//${host}:${port}/api`;

  const getAuthHeader = () => {
    const token = localStorage.getItem('auth_token');
    return {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    };
  };

  return { api, getAuthHeader };
};
