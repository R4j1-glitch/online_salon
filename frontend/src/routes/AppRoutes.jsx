import { Routes, Route } from 'react-router-dom';
import Home              from '../pages/Home.jsx';
import Login             from '../pages/Login.jsx';
import Register          from '../pages/Register.jsx';
import Salons            from '../pages/Salons.jsx';
import SalonDetails      from '../pages/SalonDetails.jsx';
import Booking           from '../pages/Booking.jsx';
import MyAppointments    from '../pages/MyAppointments.jsx';
import SalonDashboard    from '../pages/SalonDashboard.jsx';
import DesignerDashboard from '../pages/DesignerDashboard.jsx';
import ProtectedRoute    from '../components/ProtectedRoute.jsx';

export default function AppRoutes() {
  return (
    <Routes>
      <Route path="/"        element={<Home />} />
      <Route path="/salons"  element={<Salons />} />
      <Route path="/salons/:id" element={<SalonDetails />} />
      <Route path="/salons/:salonId/book" element={
        <ProtectedRoute roles={['customer']}><Booking /></ProtectedRoute>
      } />
      <Route path="/login"   element={<Login />} />
      <Route path="/register" element={<Register />} />

      <Route path="/my-appointments" element={
        <ProtectedRoute roles={['customer']}><MyAppointments /></ProtectedRoute>
      } />
      <Route path="/admin" element={
        <ProtectedRoute roles={['salon_admin']}><SalonDashboard /></ProtectedRoute>
      } />
      <Route path="/designer" element={
        <ProtectedRoute roles={['designer']}><DesignerDashboard /></ProtectedRoute>
      } />

      <Route path="*" element={
        <div className="page"><h2>404</h2><p>Page not found.</p></div>
      } />
    </Routes>
  );
}
