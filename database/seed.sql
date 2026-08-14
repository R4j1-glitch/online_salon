-- ============================================================
-- Seed Data for Salon Booking Demo
-- ============================================================
-- All demo passwords are 'password123' (bcrypt hashed).
-- IMPORTANT: This is demo data only.
-- ============================================================

USE salon_db;

-- password_hash('password123', PASSWORD_BCRYPT)
-- pre-generated hash for 'password123':
SET @pw = '$2y$10$wH8QH2VfPz4YxYj4l8m6XuGq6sH9u0wQvq0e6b6rQ7p8oYb1Xm6a.';

-- Users
INSERT INTO users (id, name, email, phone, password, role) VALUES
(1, 'Demo Customer',   'customer@example.com', '9800000001', @pw, 'customer'),
(2, 'Royal Admin',     'admin@example.com',    '9800000002', @pw, 'salon_admin'),
(3, 'Anisha Designer', 'designer@example.com', '9800000003', @pw, 'designer'),
(4, 'Bikesh Designer', 'bikesh@example.com',   '9800000004', @pw, 'designer'),
(5, 'Customer Two',    'cust2@example.com',    '9800000005', @pw, 'customer');

-- Salons
INSERT INTO salons (id, owner_id, name, description, address, phone, opening_time, closing_time) VALUES
(1, 2, 'Royal Beauty Salon', 'Premium beauty & hair salon in town.',
     'Main Street, Kathmandu', '01-4444444', '09:00:00', '19:00:00'),
(2, 2, 'Glow Hair Studio',   'Modern unisex hair studio.',
     'Lakeside, Pokhara',     '061-555555', '10:00:00', '20:00:00');

-- Designers (user_id 3,4 are designers)
INSERT INTO designers (id, salon_id, user_id, specialization, description, status) VALUES
(1, 1, 3, 'Hair Styling',    'Senior stylist, 8+ years experience.', 'active'),
(2, 1, 4, 'Hair Coloring',   'Color expert & bridal specialist.',     'active'),
(3, 2, 3, 'Cutting & Spa',   'Cuts, spa and basic styling.',           'active');

-- Services
INSERT INTO services (id, salon_id, name, description, price, duration) VALUES
(1, 1, 'Haircut',        'Standard haircut',                  500.00, 30),
(2, 1, 'Hair Coloring',  'Full hair coloring',               2500.00, 90),
(3, 1, 'Facial',         'Basic facial cleanup',              800.00, 45),
(4, 1, 'Hair Spa',       'Relaxing hair spa treatment',      1500.00, 60),
(5, 2, 'Beard Trim',     'Beard shaping and trim',            300.00, 20),
(6, 2, 'Haircut',        'Standard haircut',                  600.00, 30);

-- Designer-Service map
INSERT INTO designer_services (designer_id, service_id) VALUES
(1, 1), (1, 4),
(2, 2),
(3, 5), (3, 6);

-- Weekly availability  (Sun=0 ... Sat=6)
INSERT INTO designer_availability (designer_id, day_of_week, start_time, end_time, is_available) VALUES
(1, 0, '10:00:00', '18:00:00', 1),
(1, 1, '10:00:00', '18:00:00', 1),
(1, 2, '10:00:00', '18:00:00', 1),
(1, 3, '10:00:00', '18:00:00', 1),
(1, 4, '10:00:00', '18:00:00', 1),
(1, 5, '10:00:00', '18:00:00', 1),
(1, 6, '10:00:00', '18:00:00', 0),
(2, 0, '11:00:00', '19:00:00', 1),
(2, 1, '11:00:00', '19:00:00', 1),
(2, 2, '11:00:00', '19:00:00', 1),
(2, 3, '11:00:00', '19:00:00', 1),
(2, 4, '11:00:00', '19:00:00', 1),
(2, 5, '11:00:00', '19:00:00', 0),
(2, 6, '11:00:00', '19:00:00', 0),
(3, 0, '10:00:00', '20:00:00', 1),
(3, 1, '10:00:00', '20:00:00', 1),
(3, 2, '10:00:00', '20:00:00', 1),
(3, 3, '10:00:00', '20:00:00', 1),
(3, 4, '10:00:00', '20:00:00', 1),
(3, 5, '10:00:00', '20:00:00', 1),
(3, 6, '10:00:00', '20:00:00', 1);

-- Sample appointment
INSERT INTO appointments
  (customer_id, salon_id, designer_id, service_id, appointment_date,
   start_time, end_time, normal_price, appointment_type, status, notes)
VALUES
  (1, 1, 1, 1, '2026-08-20', '10:00:00', '10:30:00', 500.00, 'normal', 'pending', 'Please be on time.');

-- Sample urgent request draft
INSERT INTO urgent_requests
  (appointment_id, customer_id, designer_id, original_price,
   customer_extra_offer, salon_extra_offer, final_price, status, message)
VALUES
  (1, 1, 1, 500.00, 300.00, 0.00, 0.00, 'customer_proposed',
   'I really need this slot, willing to pay extra.');
