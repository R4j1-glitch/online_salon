import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../services/api.js';
import AppointmentCard from '../components/AppointmentCard.jsx';
import UrgentRequestCard from '../components/UrgentRequestCard.jsx';

export default function MyAppointments() {
  const [items, setItems]         = useState([]);
  const [urgent, setUrgent]       = useState([]);
  const [loading, setLoading]     = useState(true);
  const [err, setErr]             = useState('');

  const load = async () => {
    setLoading(true); setErr('');
    try {
      const [a, u] = await Promise.all([
        api.get('/appointments?action=index'),
        api.get('/urgent-requests?action=index'),
      ]);
      setItems(a.data.data.appointments || []);
      setUrgent(u.data.data.urgent_requests || []);
    } catch (e) {
      const status = e.response?.status;
      const msg = e.response?.data?.message || e.message || 'Failed to load.';
      setErr(`${status ? `HTTP ${status}: ` : ''}${msg}`);
    } finally {
      setLoading(false);
    }
  };
  useEffect(() => { load(); }, []);

  if (loading) return <div className="page">Loading…</div>;

  return (
    <div className="page">
      <h2>My Appointments</h2>
      {err && (
        <div className="error">
          {err} — please make sure you are <Link to="/login">logged in</Link>.
        </div>
      )}

      <h3>Appointments</h3>
      {!err && items.length === 0 && <p className="muted">You have no appointments yet.</p>}
      <div className="card-grid">
        {items.map((a) => (
          <AppointmentCard key={a.id} appt={a} role="customer" onChange={load} />
        ))}
      </div>

      <h3 style={{ marginTop: 24 }}>Urgent Requests</h3>
      {!err && urgent.length === 0 && <p className="muted">No urgent requests.</p>}
      <div className="card-grid">
        {urgent.map((u) => (
          <UrgentRequestCard key={u.id} req={u} role="customer" onChange={load} />
        ))}
      </div>
    </div>
  );
}
