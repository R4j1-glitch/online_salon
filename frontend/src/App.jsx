import Navbar     from './components/Navbar.jsx';
import AppRoutes  from './routes/AppRoutes.jsx';

export default function App() {
  return (
    <>
      <Navbar />
      <main className="container">
        <AppRoutes />
      </main>
    </>
  );
}
