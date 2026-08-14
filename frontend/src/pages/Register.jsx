import { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { useAuth } from '../context/AuthContext.jsx';

export default function Register() {
  const { register } = useAuth();
  const navigate = useNavigate();

  const [form, setForm] = useState({
    name: '', email: '', phone: '', password: '', role: 'customer',
  });
  const [showPw, setShowPw] = useState(false);
  const [error, setError] = useState('');
  const [busy, setBusy]     = useState(false);

  const set = (k) => (e) => setForm({ ...form, [k]: e.target.value });

  const submit = async (e) => {
    e.preventDefault();
    setBusy(true); setError('');
    try {
      await register(form);
      navigate('/');
    } catch (err) {
      setError(err.response?.data?.message || 'Registration failed.');
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="page narrow">
      <h2>Create Account</h2>
      {error && <div className="error">{error}</div>}
      <form onSubmit={submit} className="form">
        <label>Full Name
          <input required value={form.name} onChange={set('name')} />
        </label>
        <label>Email
          <input type="email" required value={form.email} onChange={set('email')} />
        </label>
        <label>Phone (optional)
          <input value={form.phone} onChange={set('phone')} />
        </label>
        <label>Password
          <div className="pw-wrap">
            <input type={showPw ? 'text' : 'password'} required minLength={6}
                   value={form.password} onChange={set('password')} />
            <button type="button" className="pw-eye"
                    aria-label={showPw ? 'Hide password' : 'Show password'}
                    onClick={() => setShowPw((v) => !v)}>
              {showPw ? '🙈' : '👁️'}
            </button>
          </div>
        </label>
        <label>Role
          <select value={form.role} onChange={set('role')}>
            <option value="customer">Customer</option>
            <option value="salon_admin">Salon Admin</option>
          </select>
        </label>
        <p className="muted small">
          Designer accounts are created by the salon admin from their dashboard.
        </p>
        <button className="btn primary" disabled={busy}>
          {busy ? 'Creating…' : 'Register'}
        </button>
        <p className="muted">
          Already registered? <Link to="/login">Login</Link>
        </p>
      </form>
    </div>
  );
}
