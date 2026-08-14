import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext.jsx';

export default function Navbar() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  return (
    <nav className="navbar">
      <Link to="/" className="brand">💇 SalonBook</Link>

      <div className="nav-links">
        <Link to="/">Home</Link>
        <Link to="/salons">Salons</Link>

        {!user && (
          <>
            <Link to="/login">Login</Link>
            <Link to="/register">Register</Link>
          </>
        )}

        {user?.role === 'customer' && (
          <Link to="/my-appointments">My Appointments</Link>
        )}
        {user?.role === 'salon_admin' && (
          <Link to="/admin">Admin Dashboard</Link>
        )}
        {user?.role === 'designer' && (
          <Link to="/designer">Designer Dashboard</Link>
        )}

        {user && (
          <>
            <span className="user-chip">{user.name} ({user.role})</span>
            <button className="btn-link" onClick={handleLogout}>Logout</button>
          </>
        )}
      </div>
    </nav>
  );
}
