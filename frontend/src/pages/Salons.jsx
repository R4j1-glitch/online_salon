import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../services/api.js';

export default function Salons() {
  const [salons, setSalons] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    (async () => {
      try {
        const { data } = await api.get('/salons?action=index');
        setSalons(data.data.salons || []);
      } catch (e) {
        setError(e.response?.data?.message || 'Failed to load salons.');
      } finally {
        setLoading(false);
      }
    })();
  }, []);

  if (loading) return <div className="page">Loading…</div>;
  if (error)   return <div className="page"><div className="error">{error}</div></div>;

  return (
    <div className="page">
      <h2>Browse Salons</h2>
      {salons.length === 0 && <p className="muted">No salons yet.</p>}
      <div className="card-grid">
        {salons.map((s) => (
          <Link key={s.id} to={`/salons/${s.id}`} className="card salon-card">
            <h3>{s.name}</h3>
            <p className="muted small">{s.address}</p>
            <p>{s.description}</p>
            <p className="muted small">⏰ {s.opening_time} – {s.closing_time}</p>
          </Link>
        ))}
      </div>
    </div>
  );
}
