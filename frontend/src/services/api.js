import axios from 'axios';

// Use a relative URL so Vite proxies /api → backend and the request is same-origin.
// Same-origin means the PHP session cookie is always sent automatically.
const baseURL = import.meta.env.VITE_API_URL || '/api';

const api = axios.create({
  baseURL,
  withCredentials: true,        // send PHP session cookie (safe even when same-origin)
  headers: { 'Content-Type': 'application/json' },
});

export default api;
