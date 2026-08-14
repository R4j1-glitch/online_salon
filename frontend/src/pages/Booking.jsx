import { useEffect, useMemo, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import api from '../services/api.js';

/** Generate HH:MM slots between two HH:MM:SS bounds, every `step` minutes. */
function buildSlots(start, end, step = 30) {
  if (!start || !end) return [];
  const out = [];
  const [sh, sm] = start.split(':').map(Number);
  const [eh, em] = end.split(':').map(Number);
  let cur = sh * 60 + sm;
  const last = eh * 60 + em;
  while (cur + step <= last) {
    const h = Math.floor(cur / 60), m = cur % 60;
    out.push(`${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`);
    cur += step;
  }
  return out;
}

export default function Booking() {
  const { salonId } = useParams();
  const navigate = useNavigate();

  const [salon, setSalon]       = useState(null);
  const [services, setServices] = useState([]);
  const [designers, setDesigners] = useState([]);

  const [serviceId, setServiceId]   = useState('');
  const [designerId, setDesignerId] = useState('');
  const [date, setDate]             = useState('');
  const [startTime, setStartTime]   = useState('');

  const [msg, setMsg]   = useState('');
  const [err, setErr]   = useState('');
  const [busy, setBusy] = useState(false);

  const [extraOffer, setExtraOffer] = useState('');
  const [urgentMsg,  setUrgentMsg]  = useState('');

  useEffect(() => {
    (async () => {
      try {
        const [s, sv, ds] = await Promise.all([
          api.get(`/salons?action=show&id=${salonId}`),
          api.get(`/services?action=index&salon_id=${salonId}`),
          api.get(`/designers?action=by-salon&salon_id=${salonId}`),
        ]);
        setSalon(s.data.data.salon);
        setServices((sv.data.data.services || []).filter((x) => x.status === 'active'));
        setDesigners((ds.data.data.designers || []).filter((x) => x.status === 'active'));
      } catch (e) { setErr(e.response?.data?.message || 'Failed to load.'); }
    })();
  }, [salonId]);

  const service = services.find((x) => Number(x.id) === Number(serviceId));

  // Designers filtered to those that can perform the selected service
  const eligibleDesigners = useMemo(() => {
    if (!serviceId) return designers;
    return designers; // backend will validate; we keep it simple here
  }, [designers, serviceId]);

  // Pick first active day's schedule from the chosen designer via a quick lookup
  const [dayWindow, setDayWindow] = useState(null);
  useEffect(() => {
    setStartTime(''); setDayWindow(null); setMsg(''); setErr('');
    if (!designerId || !date) return;
    (async () => {
      try {
        const r = await api.get(`/designers?action=show&id=${designerId}`);
        const list = r.data.data.designer.availability || [];
        const d = new Date(date + 'T00:00:00');
        const dow = d.getDay();
        const row = list.find((x) => Number(x.day_of_week) === dow && Number(x.is_available));
        if (row) setDayWindow({ start: String(row.start_time).slice(0,5), end: String(row.end_time).slice(0,5) });
        else setDayWindow({ start: null, end: null });
      } catch { setDayWindow({ start: null, end: null }); }
    })();
  }, [designerId, date]);

  const slots = dayWindow && dayWindow.start
    ? buildSlots(dayWindow.start, dayWindow.end, 30)
    : [];

  const checkAvailability = async () => {
    setErr(''); setMsg(''); setBusy(true);
    try {
      const { data } = await api.get('/appointments', {
        params: {
          action: 'check-availability',
          designer_id: designerId, service_id: serviceId,
          date, start_time: startTime,
        },
      });
      setMsg(data.message || 'Available');
    } catch (e) { setErr(e.response?.data?.message || 'Unavailable.'); }
    finally { setBusy(false); }
  };

  const book = async () => {
    setErr(''); setMsg(''); setBusy(true);
    try {
      const { data } = await api.post('/appointments?action=store', {
        designer_id: Number(designerId),
        service_id:  Number(serviceId),
        date, start_time: startTime,
        appointment_type: 'normal',
      });
      setMsg(data.message || 'Booked!');
      navigate('/my-appointments');
    } catch (e) { setErr(e.response?.data?.message || 'Booking failed.'); }
    finally { setBusy(false); }
  };

  const requestUrgent = async () => {
    setErr(''); setMsg(''); setBusy(true);
    try {
      const { data } = await api.post('/urgent-requests?action=store', {
        designer_id: Number(designerId),
        service_id:  Number(serviceId),
        date, start_time: startTime,
        extra_offer: parseFloat(extraOffer) || 0,
        message: urgentMsg || null,
      });
      setMsg(data.message || 'Urgent request sent.');
      navigate('/my-appointments');
    } catch (e) { setErr(e.response?.data?.message || 'Urgent request failed.'); }
    finally { setBusy(false); }
  };

  // Heuristic: if latest availability check error suggests a slot conflict,
  // show urgent-request form. We rely on the previous error message.
  const showUrgent = err && /not available|already/i.test(err);

  if (!salon) return <div className="page">{err ? <div className="error">{err}</div> : 'Loading…'}</div>;

  return (
    <div className="page">
      <h2>Book at {salon.name}</h2>
      {msg && <div className="success">{msg}</div>}
      {err && <div className="error">{err}</div>}

      <div className="form">
        <label>Service
          <select value={serviceId} onChange={(e) => setServiceId(e.target.value)}>
            <option value="">— Select —</option>
            {services.map((sv) => (
              <option key={sv.id} value={sv.id}>
                {sv.name} — Rs. {Number(sv.price).toFixed(2)} ({sv.duration} min)
              </option>
            ))}
          </select>
        </label>

        <label>Designer
          <select value={designerId} onChange={(e) => setDesignerId(e.target.value)} disabled={!serviceId}>
            <option value="">— Select —</option>
            {eligibleDesigners.map((d) => (
              <option key={d.id} value={d.id}>
                {d.user_name} {d.specialization ? `· ${d.specialization}` : ''}
              </option>
            ))}
          </select>
        </label>

        <label>Date
          <input type="date" value={date}
                 onChange={(e) => setDate(e.target.value)}
                 disabled={!designerId} min={new Date().toISOString().slice(0,10)} />
        </label>

        <div>
          <label className="muted small">Available Time Slots</label>
          {!designerId && <p className="muted small">Select a designer first.</p>}
          {designerId && date && dayWindow && !dayWindow.start && (
            <p className="muted small">Designer is not available on this day.</p>
          )}
          {designerId && date && dayWindow && dayWindow.start && (
            <div className="slot-grid">
              {slots.map((s) => (
                <button key={s}
                        className={`slot ${startTime === s ? 'selected' : ''}`}
                        onClick={() => setStartTime(s)}>
                  {s}
                </button>
              ))}
            </div>
          )}
        </div>

        <div className="row">
          <button className="btn" disabled={!serviceId || !designerId || !date || !startTime || busy}
                  onClick={checkAvailability}>
            Check Availability
          </button>
          <button className="btn primary" disabled={!startTime || busy} onClick={book}>
            Book Appointment
          </button>
        </div>

        {showUrgent && (
          <section className="card urgent-card">
            <h4>Request Urgent Appointment</h4>
            <p className="muted small">
              The slot is taken. You can send an urgent request and offer an additional amount.
              The salon/designer can accept, reject, or counter-offer.
            </p>
            <div className="row">
              <label>Extra offer (Rs.) <input type="number" min="0" step="50"
                value={extraOffer} onChange={(e) => setExtraOffer(e.target.value)} /></label>
            </div>
            <label>Message (optional) <textarea rows="2" value={urgentMsg}
              onChange={(e) => setUrgentMsg(e.target.value)} /></label>
            <button className="btn primary" disabled={busy || !serviceId || !designerId || !date || !startTime}
                    onClick={requestUrgent}>
              Send Urgent Request
            </button>
            <p className="muted small">Payment Status: Demo Only — no real charge.</p>
          </section>
        )}
      </div>
    </div>
  );
}
