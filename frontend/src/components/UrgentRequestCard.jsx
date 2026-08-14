import { useState } from 'react';
import api from '../services/api.js';

/**
 * Role-aware urgent request card.
 * role: 'customer' | 'salon_admin' | 'designer'
 */
export default function UrgentRequestCard({ req, role, onChange }) {
  const [counter, setCounter] = useState('');
  const [busy, setBusy]       = useState(false);
  const [err, setErr]         = useState('');

  const orig     = Number(req.original_price);
  const custOff  = Number(req.customer_extra_offer);
  const salonOff = Number(req.salon_extra_offer);
  const final    = Number(req.final_price);
  const live = orig + (salonOff > 0 ? salonOff : custOff);

  const call = async (action, body) => {
    setBusy(true); setErr('');
    try {
      await api.put(`/urgent-requests?action=${action}&id=${req.id}`, body || {});
      onChange?.();
    } catch (e) { setErr(e.response?.data?.message || 'Action failed.'); }
    finally { setBusy(false); }
  };

  const isOpen = ['customer_proposed', 'salon_countered'].includes(req.status);

  return (
    <div className="card appt">
      <div className="appt-head">
        <h4>Urgent · {req.service_name}</h4>
        <span className={`status status-${req.status}`}>{req.status.replace('_',' ')}</span>
      </div>
      <p className="muted small">
        {req.appointment_date} · {String(req.start_time).slice(0,5)} · {req.salon_name || ''}
      </p>
      {role !== 'customer' && req.customer_name && (
        <p>Customer: <strong>{req.customer_name}</strong></p>
      )}
      {role === 'customer' && req.designer_name && (
        <p>Designer: <strong>{req.designer_name}</strong></p>
      )}

      <table className="table small">
        <tbody>
          <tr><th>Original</th><td>Rs. {orig.toFixed(2)}</td></tr>
          <tr><th>Customer extra</th><td>Rs. {custOff.toFixed(2)}</td></tr>
          <tr><th>Salon/Designer extra</th><td>Rs. {salonOff.toFixed(2)}</td></tr>
          <tr><th>Live total</th><td><strong>Rs. {live.toFixed(2)}</strong></td></tr>
          {final > 0 && <tr><th>Final</th><td><strong>Rs. {final.toFixed(2)}</strong></td></tr>}
        </tbody>
      </table>

      {req.message && <p className="muted small">💬 {req.message}</p>}
      <p className="muted small">Payment Status: Demo Only — no real charge.</p>
      {err && <div className="error">{err}</div>}

      <div className="row">
        {/* Salon / Designer actions */}
        {role !== 'customer' && isOpen && (
          <>
            <input type="number" min="0" step="50" placeholder="Counter Rs." value={counter}
                   onChange={(e) => setCounter(e.target.value)} />
            <button className="btn small" disabled={busy || counter === ''}
                    onClick={() => call('counter-offer', { extra_offer: parseFloat(counter) || 0 })}>
              Counter
            </button>
            <button className="btn small primary" disabled={busy}
                    onClick={() => call('accept')}>Accept</button>
            <button className="btn small danger" disabled={busy}
                    onClick={() => call('reject')}>Reject</button>
          </>
        )}

        {/* Customer actions */}
        {role === 'customer' && isOpen && (
          <>
            <button className="btn small primary" disabled={busy}
                    onClick={() => call('accept')}>Accept</button>
            <button className="btn small danger" disabled={busy}
                    onClick={() => call('reject')}>Reject</button>
          </>
        )}
      </div>
    </div>
  );
}
