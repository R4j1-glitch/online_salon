import { Link } from 'react-router-dom';
import { useAuth } from '../context/AuthContext.jsx';

export default function Home() {
  const { user } = useAuth();

  return (
    <div className="page">
      <div className="hero">
        <h1>Welcome to SalonBook</h1>
        <p>Book your next salon appointment in a few clicks.</p>
        {!user && (
          <div className="hero-actions">
            <Link className="btn primary" to="/register">Get Started</Link>
            <Link className="btn" to="/login">Login</Link>
          </div>
        )}
        {user && (
          <p className="muted">
            You are signed in as <strong>{user.name}</strong> ({user.role}).
          </p>
        )}
      </div>

      <div className="card-grid">
        <div className="card">
          <h3>Browse Salons</h3>
          <p>Find trusted salons near you.</p>
        </div>
        <div className="card">
          <h3>Pick a Designer</h3>
          <p>Choose the stylist that fits your style.</p>
        </div>
        <div className="card">
          <h3>Book & Confirm</h3>
          <p>Lock a time slot instantly.</p>
        </div>
      </div>
    </div>
  );
}
