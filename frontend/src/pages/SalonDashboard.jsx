import { useEffect, useState } from 'react';
import api from '../services/api.js';
import AppointmentCard from '../components/AppointmentCard.jsx';
import UrgentRequestCard from '../components/UrgentRequestCard.jsx';

const emptySalon    = { name: '', description: '', address: '', phone: '', opening_time: '09:00:00', closing_time: '19:00:00' };
const emptyService  = { name: '', description: '', price: 0, duration: 30, status: 'active' };
const emptyDesigner = {
  name: '', email: '', phone: '', password: 'designer123',
  specialization: '', description: '', status: 'active', service_ids: [],
};

export default function SalonDashboard() {
  const [tab, setTab] = useState('salon');

  /* --- Salon state --- */
  const [salon, setSalon]         = useState(null);
  const [salonForm, setSalonForm] = useState(emptySalon);
  const [msg, setMsg]   = useState('');
  const [err, setErr]   = useState('');
  const [busy, setBusy] = useState(false);

  /* --- Services state --- */
  const [services, setServices]   = useState([]);
  const [svcForm, setSvcForm]     = useState(emptyService);
  const [editingSvc, setEditingSvc] = useState(null);

  /* --- Designers state --- */
  const [designers, setDesigners]     = useState([]);
  const [dsgForm, setDsgForm]         = useState(emptyDesigner);
  const [editingDsg, setEditingDsg]   = useState(null);

  /* --- Appointments state --- */
  const [appointments, setAppointments] = useState([]);

  const loadAppointments = async () => {
    try {
      const { data } = await api.get('/appointments?action=index');
      setAppointments(data.data.appointments || []);
    } catch {/* ignore */}
  };

  /* --- Urgent state --- */
  const [urgent, setUrgent] = useState([]);
  const loadUrgent = async () => {
    try {
      const { data } = await api.get('/urgent-requests?action=index');
      setUrgent(data.data.urgent_requests || []);
    } catch {/* ignore */}
  };

  const loadAll = async () => {
    try {
      const mine = await api.get('/salons?action=mine');
      const s = mine.data.data.salon;
      setSalon(s);
      if (s) {
        setSalonForm({
          name: s.name || '', description: s.description || '',
          address: s.address || '', phone: s.phone || '',
          opening_time: s.opening_time || '09:00:00',
          closing_time: s.closing_time || '19:00:00',
        });
        const [sv, ds] = await Promise.all([
          api.get(`/services?action=index&salon_id=${s.id}`),
          api.get(`/designers?action=by-salon&salon_id=${s.id}`),
        ]);
        setServices(sv.data.data.services || []);
        setDesigners(ds.data.data.designers || []);
      } else {
        setSalonForm(emptySalon); setServices([]); setDesigners([]);
      }
    } catch (e) {
      setErr(e.response?.data?.message || 'Failed to load.');
    }
  };

  useEffect(() => { loadAll(); loadAppointments(); loadUrgent(); }, []);

  /* --- Salon handlers --- */
  const saveSalon = async (e) => {
    e.preventDefault();
    setBusy(true); setMsg(''); setErr('');
    try {
      await api.post('/salons?action=store', salonForm);
      setMsg('Salon saved.');
      await loadAll();
    } catch (ex) { setErr(ex.response?.data?.message || 'Failed to save salon.'); }
    finally { setBusy(false); }
  };

  /* --- Service handlers --- */
  const saveService = async (e) => {
    e.preventDefault();
    setBusy(true); setMsg(''); setErr('');
    try {
      if (editingSvc) {
        await api.put(`/services?action=update&id=${editingSvc.id}`, svcForm);
      } else {
        await api.post('/services?action=store', svcForm);
      }
      setMsg('Service saved.');
      setSvcForm(emptyService); setEditingSvc(null);
      await loadAll();
    } catch (ex) { setErr(ex.response?.data?.message || 'Failed to save service.'); }
    finally { setBusy(false); }
  };

  const editService = (sv) => {
    setEditingSvc(sv);
    setSvcForm({ name: sv.name, description: sv.description || '',
      price: sv.price, duration: sv.duration, status: sv.status });
  };

  const deleteService = async (sv) => {
    if (!confirm(`Delete service "${sv.name}"?`)) return;
    try { await api.delete(`/services?action=destroy&id=${sv.id}`); await loadAll(); }
    catch (ex) { setErr(ex.response?.data?.message || 'Failed to delete.'); }
  };

  /* --- Designer handlers --- */
  const openCreateDesigner = () => {
    setEditingDsg(null);
    setDsgForm(emptyDesigner);
  };

  const editDesigner = async (d) => {
    setEditingDsg(d);
    // Fetch full record to know current service_ids
    try {
      const r = await api.get(`/designers?action=show&id=${d.id}`);
      const full = r.data.data.designer;
      setDsgForm({
        name: full.user_name, email: full.user_email, phone: '',
        specialization: full.specialization || '', description: full.description || '',
        status: full.status,
        service_ids: full.services || [],
        password: '',  // not edited here
      });
    } catch {/* keep basic fields */}
  };

  const saveDesigner = async (e) => {
    e.preventDefault();
    setBusy(true); setMsg(''); setErr('');
    try {
      if (editingDsg) {
        await api.put(`/designers?action=update&id=${editingDsg.id}`, {
          specialization: dsgForm.specialization,
          description: dsgForm.description,
          status: dsgForm.status,
          service_ids: dsgForm.service_ids,
        });
      } else {
        await api.post('/designers?action=store', dsgForm);
      }
      setMsg('Designer saved.');
      setDsgForm(emptyDesigner); setEditingDsg(null);
      await loadAll();
    } catch (ex) { setErr(ex.response?.data?.message || 'Failed to save designer.'); }
    finally { setBusy(false); }
  };

  const deleteDesigner = async (d) => {
    if (!confirm(`Delete designer "${d.user_name}"? Their login account will remain.`)) return;
    try { await api.delete(`/designers?action=destroy&id=${d.id}`); await loadAll(); }
    catch (ex) { setErr(ex.response?.data?.message || 'Failed to delete.'); }
  };

  const toggleService = (sid) => {
    setDsgForm((f) => {
      const has = f.service_ids.includes(sid);
      return { ...f, service_ids: has ? f.service_ids.filter((x) => x !== sid) : [...f.service_ids, sid] };
    });
  };

  return (
    <div className="page">
      <h2>Salon Admin Dashboard</h2>
      {msg && <div className="success">{msg}</div>}
      {err && <div className="error">{err}</div>}

      <div className="tabs">
        <button className={`btn ${tab === 'salon' ? 'primary' : ''}`} onClick={() => setTab('salon')}>Salon</button>
        <button className={`btn ${tab === 'services' ? 'primary' : ''}`} onClick={() => setTab('services')} disabled={!salon}>Services</button>
        <button className={`btn ${tab === 'designers' ? 'primary' : ''}`} onClick={() => setTab('designers')} disabled={!salon}>Designers</button>
        <button className={`btn ${tab === 'appointments' ? 'primary' : ''}`} onClick={() => { setTab('appointments'); loadAppointments(); }}>Appointments</button>
        <button className={`btn ${tab === 'urgent' ? 'primary' : ''}`} onClick={() => { setTab('urgent'); loadUrgent(); }}>Urgent</button>
      </div>

      {/* ===== Salon tab ===== */}
      {tab === 'salon' && (
        <section className="card">
          <h3>{salon ? 'My Salon' : 'Create Salon'}</h3>
          <form className="form" onSubmit={saveSalon}>
            <label>Name <input required value={salonForm.name}
              onChange={(e) => setSalonForm({ ...salonForm, name: e.target.value })} /></label>
            <label>Description <textarea rows="2" value={salonForm.description}
              onChange={(e) => setSalonForm({ ...salonForm, description: e.target.value })} /></label>
            <label>Address <input value={salonForm.address}
              onChange={(e) => setSalonForm({ ...salonForm, address: e.target.value })} /></label>
            <label>Phone <input value={salonForm.phone}
              onChange={(e) => setSalonForm({ ...salonForm, phone: e.target.value })} /></label>
            <div className="row">
              <label>Opening <input type="time" value={salonForm.opening_time}
                onChange={(e) => setSalonForm({ ...salonForm, opening_time: e.target.value })} /></label>
              <label>Closing <input type="time" value={salonForm.closing_time}
                onChange={(e) => setSalonForm({ ...salonForm, closing_time: e.target.value })} /></label>
            </div>
            <button className="btn primary" disabled={busy}>Save Salon</button>
          </form>
        </section>
      )}

      {/* ===== Services tab ===== */}
      {tab === 'services' && salon && (
        <section className="card">
          <h3>Services</h3>
          {services.length === 0 ? <p className="muted">No services yet.</p> : (
            <table className="table">
              <thead><tr><th>Name</th><th>Price</th><th>Duration</th><th>Status</th><th></th></tr></thead>
              <tbody>
                {services.map((sv) => (
                  <tr key={sv.id}>
                    <td>{sv.name}</td>
                    <td>Rs. {Number(sv.price).toFixed(2)}</td>
                    <td>{sv.duration} min</td>
                    <td>{sv.status}</td>
                    <td>
                      <button className="btn small" onClick={() => editService(sv)}>Edit</button>
                      <button className="btn small danger" onClick={() => deleteService(sv)}>Delete</button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
          <h4>{editingSvc ? 'Edit Service' : 'Add Service'}</h4>
          <form className="form" onSubmit={saveService}>
            <label>Name <input required value={svcForm.name}
              onChange={(e) => setSvcForm({ ...svcForm, name: e.target.value })} /></label>
            <label>Description <input value={svcForm.description}
              onChange={(e) => setSvcForm({ ...svcForm, description: e.target.value })} /></label>
            <div className="row">
              <label>Price <input type="number" min="0" step="0.01" required value={svcForm.price}
                onChange={(e) => setSvcForm({ ...svcForm, price: parseFloat(e.target.value) || 0 })} /></label>
              <label>Duration (min) <input type="number" min="5" step="5" value={svcForm.duration}
                onChange={(e) => setSvcForm({ ...svcForm, duration: parseInt(e.target.value) || 30 })} /></label>
              <label>Status
                <select value={svcForm.status}
                  onChange={(e) => setSvcForm({ ...svcForm, status: e.target.value })}>
                  <option value="active">active</option><option value="inactive">inactive</option>
                </select>
              </label>
            </div>
            <div className="row">
              <button className="btn primary" disabled={busy}>{editingSvc ? 'Update' : 'Create'} Service</button>
              {editingSvc && <button type="button" className="btn" onClick={() => { setEditingSvc(null); setSvcForm(emptyService); }}>Cancel</button>}
            </div>
          </form>
        </section>
      )}

      {/* ===== Designers tab ===== */}
      {tab === 'designers' && salon && (
        <section className="card">
          <h3>Designers</h3>
          {designers.length === 0 ? <p className="muted">No designers yet.</p> : (
            <table className="table">
              <thead><tr><th>Name</th><th>Email</th><th>Specialization</th><th>Status</th><th></th></tr></thead>
              <tbody>
                {designers.map((d) => (
                  <tr key={d.id}>
                    <td>{d.user_name}</td>
                    <td>{d.user_email}</td>
                    <td>{d.specialization || '-'}</td>
                    <td>{d.status}</td>
                    <td>
                      <button className="btn small" onClick={() => editDesigner(d)}>Edit</button>
                      <button className="btn small danger" onClick={() => deleteDesigner(d)}>Delete</button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
          <h4>{editingDsg ? 'Edit Designer' : 'Add Designer'}</h4>
          <form className="form" onSubmit={saveDesigner}>
            {!editingDsg && (
              <>
                <div className="row">
                  <label>Name <input required value={dsgForm.name}
                    onChange={(e) => setDsgForm({ ...dsgForm, name: e.target.value })} /></label>
                  <label>Email <input type="email" required value={dsgForm.email}
                    onChange={(e) => setDsgForm({ ...dsgForm, email: e.target.value })} /></label>
                </div>
                <div className="row">
                  <label>Phone <input value={dsgForm.phone}
                    onChange={(e) => setDsgForm({ ...dsgForm, phone: e.target.value })} /></label>
                  <label>Initial Password <input value={dsgForm.password}
                    onChange={(e) => setDsgForm({ ...dsgForm, password: e.target.value })} /></label>
                </div>
              </>
            )}
            <label>Specialization <input value={dsgForm.specialization}
              onChange={(e) => setDsgForm({ ...dsgForm, specialization: e.target.value })} /></label>
            <label>Description <textarea rows="2" value={dsgForm.description}
              onChange={(e) => setDsgForm({ ...dsgForm, description: e.target.value })} /></label>
            <label>Status
              <select value={dsgForm.status}
                onChange={(e) => setDsgForm({ ...dsgForm, status: e.target.value })}>
                <option value="active">active</option><option value="inactive">inactive</option>
              </select>
            </label>
            <fieldset className="fieldset">
              <legend>Assigned Services</legend>
              {services.length === 0
                ? <p className="muted small">Create services first.</p>
                : services.map((sv) => (
                    <label key={sv.id} className="check">
                      <input type="checkbox"
                        checked={dsgForm.service_ids.includes(sv.id)}
                        onChange={() => toggleService(sv.id)} />
                      {sv.name} <span className="muted small">(Rs. {Number(sv.price).toFixed(0)})</span>
                    </label>
                  ))}
            </fieldset>
            <div className="row">
              <button className="btn primary" disabled={busy}>{editingDsg ? 'Update' : 'Create'} Designer</button>
              {editingDsg && <button type="button" className="btn" onClick={openCreateDesigner}>Cancel</button>}
            </div>
          </form>
        </section>
      )}

      {/* ===== Appointments tab ===== */}
      {tab === 'appointments' && (
        <section className="card">
          <h3>Appointments</h3>
          {appointments.length === 0
            ? <p className="muted">No appointments yet.</p>
            : (
              <div className="card-grid">
                {appointments.map((a) => (
                  <AppointmentCard key={a.id} appt={a} role="salon_admin" onChange={loadAppointments} />
                ))}
              </div>
            )}
        </section>
      )}

      {/* ===== Urgent tab ===== */}
      {tab === 'urgent' && (
        <section className="card">
          <h3>Urgent Requests</h3>
          {urgent.length === 0
            ? <p className="muted">No urgent requests yet.</p>
            : (
              <div className="card-grid">
                {urgent.map((u) => (
                  <UrgentRequestCard key={u.id} req={u} role="salon_admin" onChange={loadUrgent} />
                ))}
              </div>
            )}
        </section>
      )}
    </div>
  );
}
