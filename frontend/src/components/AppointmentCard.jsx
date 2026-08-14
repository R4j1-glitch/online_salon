import api from '../services/api.js';

/**
 * Renders a single appointment with role-aware actions.
 * Used by customer, salon admin and designer dashboards.
 */
export default function AppointmentCard({ appt, role, onChange }) {
  const call = async (action) => {
    try {
      await api.put(`/appointments?action=${action}&id=${appt.id}`);
      onChange?.();
    } catch (e) {
      alert(e.response?.data?.message || 'Action failed.');
    }
  };

  const cancel = async () => {
    if (!confirm('Cancel this appointment?')) return;
    try {
      await api.put(`/appointments?action=cancel&id=${appt.id}`);
      onChange?.();
    } catch (e) {
      alert(e.response?.data?.message || 'Cancel failed.');
    }
  };

  const pendingLike = ['pending', 'urgent_pending'].includes(appt.status);
  const acceptedLike = ['accepted', 'urgent_accepted'].includes(appt.status);

  return (
    <div className="card appt">
      <div className="appt-head">
        <h4>
          {appt.salon_name || appt.service_name} · {appt.service_name}
        </h4>
        <span className={`status status-${appt.status}`}>{appt.status.replace('_', ' ')}</span>
      </div>
      <p className="muted small">
        {appt.appointment_date} · {String(appt.start_time).slice(0, 5)} – {String(appt.end_time).slice(0, 5)}
      </p>
      {appt.designer_name && <p>Designer: <strong>{appt.designer_name}</strong></p>}
      {appt.customer_name && <p>Customer: <strong>{appt.customer_name}</strong></p>}
      {appt.notes && <p className="muted small">📝 {appt.notes}</p>}
      <p>Price: Rs. {Number(appt.normal_price).toFixed(2)}
        {appt.appointment_type === 'urgent' && <span className="badge urgent">Urgent</span>}
      </p>

      <div className="row">
        {role !== 'customer' && pendingLike && (
          <>
            <button className="btn small primary" onClick={() => call('accept')}>Accept</button>
            <button className="btn small danger"  onClick={() => call('reject')}>Reject</button>
          </>
        )}
        {role !== 'customer' && acceptedLike && (
          <button className="btn small primary" onClick={() => call('complete')}>Mark Completed</button>
        )}
        {role === 'customer' && !['completed', 'cancelled', 'rejected', 'urgent_rejected'].includes(appt.status) && (
          <button className="btn small danger" onClick={cancel}>Cancel</button>
        )}
      </div>
    </div>
  );
}
