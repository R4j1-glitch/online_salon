import { useEffect, useState } from 'react';
import api from '../services/api.js';
import AppointmentCard from '../components/AppointmentCard.jsx';
import UrgentRequestCard from '../components/UrgentRequestCard.jsx';

const DAYS = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

const blankDay = (day) => ({
  day_of_week: day,
  start_time:  '10:00',
  end_time:    '18:00',
  is_available: 0,
});

export default function DesignerDashboard() {
  const [designer, setDesigner] = useState(null);
  const [schedule, setSchedule] = useState(DAYS.map((_, i) => blankDay(i)));
  const [appointments, setAppointments] = useState([]);
  const [msg, setMsg] = useState('');
  const [err, setErr] = useState('');
  const [busy, setBusy] = useState(false);

  const loadAppointments = async () => {
    try {
      const { data } = await api.get('/appointments?action=index');
      setAppointments(data.data.appointments || []);
    } catch {/* ignore */}
  };

  const [urgent, setUrgent] = useState([]);
  const loadUrgent = async () => {
    try {
      const { data } = await api.get('/urgent-requests?action=index');
      setUrgent(data.data.urgent_requests || []);
    } catch {/* ignore */}
  };

  useEffect(() => {
    (async () => {
      try {
        const { data } = await api.get('/designers?action=mine');
        const d = data.data.designer;
        setDesigner(d);

        // Build a 7-day skeleton, overlaying server rows
        const base = DAYS.map((_, i) => blankDay(i));
        (d.availability || []).forEach((r) => {
          const i = parseInt(r.day_of_week, 10);
          if (i >= 0 && i < 7) {
            base[i] = {
              day_of_week: i,
              start_time: String(r.start_time).slice(0, 5),
              end_time:   String(r.end_time).slice(0, 5),
              is_available: Number(r.is_available),
            };
          }
        });
        setSchedule(base);
        await loadAppointments();
        await loadUrgent();
      } catch (e) {
        setErr(e.response?.data?.message || 'Failed to load.');
      }
    })();
  }, []);

  const updateRow = (i, key, val) => {
    setSchedule((s) => s.map((row, idx) => idx === i ? { ...row, [key]: val } : row));
  };

  const save = async (e) => {
    e.preventDefault();
    setBusy(true); setMsg(''); setErr('');
    try {
      await api.put('/designers?action=availability', {
        schedule: schedule.map((r) => ({
          day_of_week: r.day_of_week,
          start_time:  r.start_time,
          end_time:    r.end_time,
          is_available: r.is_available ? 1 : 0,
        })),
      });
      setMsg('Availability saved.');
    } catch (ex) { setErr(ex.response?.data?.message || 'Failed to save.'); }
    finally { setBusy(false); }
  };

  if (!designer) return <div className="page">{err ? <div className="error">{err}</div> : 'Loading…'}</div>;

  return (
    <div className="page">
      <h2>Designer Dashboard</h2>
      {msg && <div className="success">{msg}</div>}
      {err && <div className="error">{err}</div>}

      <section className="card">
        <h3>Profile</h3>
        <p><strong>{designer.user_name}</strong> · {designer.user_email}</p>
        <p className="muted">Specialization: {designer.specialization || '-'}</p>
        <p className="muted">Salon: {designer.salon_name}</p>
        <p className="muted">Services assigned: {designer.services.length}</p>
      </section>

      <section className="card">
        <h3>Weekly Availability</h3>
        <form className="form" onSubmit={save}>
          <table className="table">
            <thead>
              <tr><th>Day</th><th>Available</th><th>Start</th><th>End</th></tr>
            </thead>
            <tbody>
              {schedule.map((row, i) => (
                <tr key={row.day_of_week}>
                  <td>{DAYS[i]}</td>
                  <td>
                    <input type="checkbox" checked={!!row.is_available}
                      onChange={(e) => updateRow(i, 'is_available', e.target.checked ? 1 : 0)} />
                  </td>
                  <td>
                    <input type="time" value={row.start_time}
                      disabled={!row.is_available}
                      onChange={(e) => updateRow(i, 'start_time', e.target.value)} />
                  </td>
                  <td>
                    <input type="time" value={row.end_time}
                      disabled={!row.is_available}
                      onChange={(e) => updateRow(i, 'end_time', e.target.value)} />
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          <button className="btn primary" disabled={busy}>Save Availability</button>
        </form>
      </section>

      <section className="card">
        <h3>My Appointments</h3>
        {appointments.length === 0
          ? <p className="muted">No appointments assigned.</p>
          : (
            <div className="card-grid">
              {appointments.map((a) => (
                <AppointmentCard key={a.id} appt={a} role="designer" onChange={loadAppointments} />
              ))}
            </div>
          )}
      </section>

      <section className="card">
        <h3>Urgent Requests</h3>
        {urgent.length === 0
          ? <p className="muted">No urgent requests.</p>
          : (
            <div className="card-grid">
              {urgent.map((u) => (
                <UrgentRequestCard key={u.id} req={u} role="designer" onChange={loadUrgent} />
              ))}
            </div>
          )}
      </section>
    </div>
  );
}
