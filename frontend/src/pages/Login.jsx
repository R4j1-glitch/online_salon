import { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { useAuth } from '../context/AuthContext.jsx';

export default function Login() {
  const { login } = useAuth();
  const navigate = useNavigate();

  const [email, setEmail]       = useState('customer@example.com');
  const [password, setPassword] = useState('password123');
  const [showPw, setShowPw]     = useState(false);
  const [error, setError]       = useState('');
  const [busy, setBusy]         = useState(false);

  const submit = async (e) => {
    e.preventDefault();
    setBusy(true);
    setError('');
    try {
      await login(email, password);
      navigate('/');
    } catch (err) {
      setError(err.response?.data?.message || 'Login failed.');
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="page narrow">
      <h2>Login</h2>
      {error && <div className="error">{error}</div>}
      <form onSubmit={submit} className="form">
        <label>Email
          <input type="email" required value={email}
                 onChange={(e) => setEmail(e.target.value)} />
        </label>
        <label>Password
          <div className="pw-wrap">
            <input type={showPw ? 'text' : 'password'} required value={password}
                   onChange={(e) => setPassword(e.target.value)} />
            <button type="button" className="pw-eye"
                    aria-label={showPw ? 'Hide password' : 'Show password'}
                    onClick={() => setShowPw((v) => !v)}>
              {showPw ? '🙈' : '👁️'}
            </button>
          </div>
        </label>
        <button className="btn primary" disabled={busy}>
          {busy ? 'Logging in…' : 'Login'}
        </button>
        <p className="muted">
          No account? <Link to="/register">Register here</Link>
        </p>
        <p className="muted small">
          Demo credentials are pre-filled. (customer@example.com / password123)
        </p>
      </form>
    </div>
  );
}
