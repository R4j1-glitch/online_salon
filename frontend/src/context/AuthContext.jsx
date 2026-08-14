import { createContext, useContext, useEffect, useState } from 'react';
import api from '../services/api.js';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  // Check existing session on first load
  useEffect(() => {
    (async () => {
      try {
        const { data } = await api.get('/auth/me?action=me');
        if (data?.success) setUser(data.data.user);
      } catch {
        setUser(null);
      } finally {
        setLoading(false);
      }
    })();
  }, []);

  const login = async (email, password) => {
    const { data } = await api.post('/auth/login?action=login', { email, password });
    setUser(data.data.user);
    return data;
  };

  const register = async (payload) => {
    const { data } = await api.post('/auth/register?action=register', payload);
    setUser(data.data.user);
    return data;
  };

  const logout = async () => {
    try { await api.post('/auth/logout?action=logout'); } catch {}
    setUser(null);
  };

  return (
    <AuthContext.Provider value={{ user, loading, login, register, logout }}>
      {children}
    </AuthContext.Provider>
  );
}

export const useAuth = () => useContext(AuthContext);
