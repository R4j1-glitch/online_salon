import { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import api from '../services/api.js';

export default function SalonDetails() {
  const { id } = useParams();
  const [salon, setSalon]       = useState(null);
  const [services, setServices] = useState([]);
  const [loading, setLoading]   = useState(true);
  const [error, setError]       = useState('');

  useEffect(() => {
    (async () => {
      try {
        const [s, sv] = await Promise.all([
          api.get(`/salons?action=show&id=${id}`),
          api.get(`/services?action=index&salon_id=${id}`),
        ]);
        setSalon(s.data.data.salon);
        setServices(sv.data.data.services || []);
      } catch (e) {
        setError(e.response?.data?.message || 'Failed to load.');
      } finally {
        setLoading(false);
      }
    })();
  }, [id]);

  if (loading) return <div className="page">Loading…</div>;
  if (error)   return <div className="page"><div className="error">{error}</div></div>;
  if (!salon)  return <div className="page">Not found.</div>;

  return (
    <div className="page">
      <div className="row spread">
        <h2>{salon.name}</h2>
        <Link className="btn primary" to={`/salons/${salon.id}/book`}>Book Appointment</Link>
      </div>
      <p className="muted">{salon.address} · � {salon.phone}</p>
      <p>{salon.description}</p>
      <p className="muted small">Hours: {salon.opening_time} – {salon.closing_time}</p>

      <h3>Services</h3>
      {services.length === 0 && <p className="muted">No services yet.</p>}
      <div className="card-grid">
        {services.map((sv) => (
          <div key={sv.id} className="card">
            <h4>{sv.name}</h4>
            <p className="muted small">{sv.description}</p>
            <p>Rs. {Number(sv.price).toFixed(2)} · {sv.duration} min</p>
          </div>
        ))}
      </div>
    </div>
  );
}
