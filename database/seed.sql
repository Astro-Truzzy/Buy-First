-- ============================================================================
-- BuyFirst seed data — realistic starting content for development.
--
-- Demo accounts (passwords are bcrypt-hashed in the users table):
--   Admin:    admin@buyfirst.test    / Admin123!
--   Customer: customer@buyfirst.test / Customer123!
--
-- IMAGE SOURCING: all product/campaign photography is hotlinked from
-- Unsplash (unsplash.com), which licenses images for commercial use without
-- attribution. For a real deployment you would download the files into
-- public/uploads/ and serve them yourself.
-- ============================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------- roles
INSERT INTO roles (id, name) VALUES (1, 'admin'), (2, 'customer');

-- ---------------------------------------------------------------- users
INSERT INTO users (id, role_id, first_name, last_name, email, password_hash, is_member, newsletter_opt_in, email_verified_at) VALUES
(1, 1, 'Ava',    'Whitfield', 'admin@buyfirst.test',    '$2y$10$WQPbCiZjSxHTEcWKJx9R1usdKHP2TJOYIiVDssP0A6bhj0HGwj026', 0, 0, NOW()),
(2, 2, 'Jordan', 'Okafor',    'customer@buyfirst.test', '$2y$10$8YDRO8ovG.Vqf5UKDA3G3OXqzcPTollFCMgZX9cOvVRwJg8hDWMc6', 1, 1, NOW()),
(3, 2, 'Maya',   'Reid',      'maya.reid@example.com',  '$2y$10$8YDRO8ovG.Vqf5UKDA3G3OXqzcPTollFCMgZX9cOvVRwJg8hDWMc6', 0, 1, NOW());

-- ---------------------------------------------------------------- addresses
INSERT INTO addresses (user_id, label, first_name, last_name, line1, line2, city, postcode, country, phone, is_default_shipping, is_default_billing) VALUES
(2, 'Home', 'Jordan', 'Okafor', '12 Allen Avenue', NULL, 'Ikeja', '100271', 'NG', '+234 803 555 0123', 1, 1),
(2, 'Work', 'Jordan', 'Okafor', '15 Adeola Odeku Street', 'Victoria Island', 'Lagos', '101241', 'NG', NULL, 0, 0),
(3, 'Home', 'Maya', 'Reid', '8 Admiralty Way', 'Lekki Phase 1', 'Lagos', '105102', 'NG', '+234 809 555 0456', 1, 1);

-- ---------------------------------------------------------------- categories
INSERT INTO categories (id, name, slug, description, sort_order) VALUES
(1, 'Shoes',       'shoes',       'Footwear engineered for speed, court control and everyday miles.', 1),
(2, 'Clothing',    'clothing',    'Performance apparel that moves when you do.', 2),
(3, 'Accessories', 'accessories', 'The finishing touches: caps, bags and socks built to train.', 3);

-- ---------------------------------------------------------------- products
INSERT INTO products (id, category_id, name, slug, description, details, gender, sport, price, sale_price, badge, is_featured) VALUES
(1, 1, 'Velocity Runner', 'velocity-runner',
 'Our fastest daily trainer yet. A full-length responsive foam core returns energy with every stride, while the engineered mesh upper keeps airflow constant from mile one to mile ten.',
 'Responsive full-length foam midsole\nEngineered mesh upper\nHeel-to-toe drop: 8mm\nWeight: 260g (EU 43)\nRubber outsole with high-wear zones',
 'men', 'running', 129990.00, NULL, 'best-seller', 1),

(2, 1, 'Stride Pulse 2', 'stride-pulse-2',
 'The second generation of our most-loved women''s road shoe. A softer heel landing, a wider forefoot and a locked-in midfoot wrap deliver comfort that lasts the long run.',
 'Dual-density cushioning\nBreathable knit upper\nHeel-to-toe drop: 10mm\nWeight: 225g (EU 40)\nReflective heel detail for low light',
 'women', 'running', 119990.00, NULL, 'just-in', 1),

(3, 1, 'Apex Court Pro', 'apex-court-pro',
 'Built for players who create separation. A low-profile cushioning unit keeps you close to the floor for quick cuts, and the wrap-around traction pattern grips through every change of direction.',
 'Low-profile court cushioning\nHerringbone traction pattern\nReinforced toe cap\nPadded ankle collar\nWeight: 340g (EU 43)',
 'men', 'basketball', 139990.00, NULL, NULL, 1),

(4, 1, 'Ignite Trainer', 'ignite-trainer',
 'One shoe for the whole session. A flat, stable base supports heavy lifts while the flexible forefoot keeps you springy through circuits, sled pushes and box jumps.',
 'Stable heel platform for lifting\nFlexible forefoot for agility work\nAbrasion-resistant upper\nInternal midfoot cage\nWeight: 300g (EU 43)',
 'men', 'training', 94990.00, 74990.00, NULL, 0),

(5, 1, 'Kinetic Flow', 'kinetic-flow',
 'Studio to street without missing a beat. Lightweight cushioning and a seamless one-piece upper make the Kinetic Flow disappear on your foot, whatever the class throws at you.',
 'One-piece seamless upper\nLightweight EVA midsole\nPivot point under forefoot\nMachine-washable\nWeight: 210g (EU 38)',
 'women', 'training', 99990.00, NULL, NULL, 0),

(6, 1, 'Pace Chaser Trail', 'pace-chaser-trail',
 'When the pavement ends, the Pace Chaser starts. Aggressive 5mm lugs bite into mud and gravel, a rock plate shields your midfoot, and the water-shedding upper drains fast after stream crossings.',
 '5mm multi-directional lugs\nProtective rock plate\nQuick-drain mesh upper\nGusseted tongue keeps grit out\nWeight: 295g (EU 43)',
 'men', 'running', 149990.00, NULL, 'limited-drop', 1),

(7, 1, 'Metro Ease', 'metro-ease',
 'The everyday icon. Clean lines, a cushioned sole you can stand in all day, and a durable leather-and-textile upper that looks sharper the longer you wear it.',
 'Full-grain leather and textile upper\nAll-day cushioned midsole\nLow-key branding\nDurable cupsole construction\nWeight: 320g (EU 43)',
 'unisex', 'lifestyle', 84990.00, NULL, 'best-seller', 1),

(8, 1, 'Halo Glide', 'halo-glide',
 'A sculpted lifestyle silhouette with running DNA. The exaggerated midsole delivers genuine comfort, not just looks, and the muted tonal palette goes with everything in your rotation.',
 'Sculpted foam midsole\nTonal suede and mesh upper\nPull tab for easy on/off\nPadded collar\nWeight: 250g (EU 38)',
 'women', 'lifestyle', 89990.00, 64990.00, NULL, 0),

(9, 1, 'First Touch FG', 'first-touch-fg',
 'Control the game from your first touch. A textured microfibre upper gives clean contact on the ball, and the firm-ground stud pattern keeps you planted through tackles and turns.',
 'Textured microfibre upper\nFirm-ground (FG) stud configuration\nCompression-moulded sockliner\nAsymmetric lacing for a bigger strike zone\nWeight: 215g (EU 43)',
 'men', 'football', 109990.00, NULL, NULL, 0),

(10, 1, 'Skyline Jr', 'skyline-jr',
 'Big-kid style, built for the playground. Easy hook-and-loop straps mean they can put them on themselves, and the tough rubber outsole survives scooters, climbing frames and everything between.',
 'Hook-and-loop strap closure\nReinforced toe bumper\nCushioned insole\nGrippy rubber outsole\nSizes EU 32 to EU 37',
 'kids', 'lifestyle', 54990.00, NULL, NULL, 0),

(11, 1, 'Sprint Spark Jr', 'sprint-spark-jr',
 'For sports day and every day. Lightweight, flexible and breathable, the Sprint Spark Jr keeps up with fast-growing feet at a price that keeps up with them too.',
 'Lightweight flexible midsole\nBreathable mesh upper\nElastic laces with lock toggle\nMachine-washable\nSizes EU 32 to EU 37',
 'kids', 'running', 49990.00, 39990.00, NULL, 0),

(12, 1, 'Baseline Lo', 'baseline-lo',
 'Court heritage, cut low. A BuyFirst Club exclusive that pairs a buttery leather upper with modern cushioning — clean enough for the office, tough enough for the weekend.',
 'Premium leather upper\nLow-cut silhouette\nModern drop-in cushioning\nMember-exclusive colourway\nWeight: 290g (EU 40)',
 'women', 'basketball', 99990.00, NULL, 'member-exclusive', 0),

(13, 2, 'Core Performance Tee', 'core-performance-tee',
 'The training tee you will reach for first. Sweat-wicking fabric with a soft hand feel, a longer back hem that stays put, and flatlock seams that never chafe.',
 'Sweat-wicking knit fabric\nFlatlock seams\nDrop-tail hem\nRelaxed athletic fit\n100% recycled polyester',
 'men', 'training', 29990.00, NULL, NULL, 0),

(14, 2, 'Tempo Run Shorts', 'tempo-run-shorts',
 'Seven inches of freedom. An ultralight woven shell over a supportive inner brief, with a zip pocket that actually fits your phone and keys.',
 '7in (18cm) inseam\nUltralight woven shell\nSupportive inner brief\nZippered back pocket\nReflective side splits',
 'men', 'running', 39990.00, NULL, NULL, 0),

(15, 2, 'Foundation Hoodie', 'foundation-hoodie',
 'Heavyweight fleece with a structured fit — the hoodie that holds its shape wash after wash. An oversized hood, ribbed panels and a double-layered kangaroo pocket finish it properly.',
 '400gsm brushed-back fleece\nStructured oversized hood\nRibbed side panels\nDouble-layer kangaroo pocket\n80% cotton, 20% recycled polyester',
 'men', 'lifestyle', 64990.00, NULL, 'best-seller', 1),

(16, 2, 'Momentum Leggings', 'momentum-leggings',
 'Squat-proof, sweat-wicking and genuinely high-waisted. A wide sculpting waistband with a hidden pocket, and four-way stretch that moves with every rep.',
 'Four-way stretch fabric\nHigh sculpting waistband\nHidden waistband pocket\nSquat-proof opacity tested\nFull length, 71cm inseam',
 'women', 'training', 59990.00, NULL, 'best-seller', 1),

(17, 2, 'Balance Seamless Bra', 'balance-seamless-bra',
 'Medium support with zero distractions. Seamless knit construction removes pressure points, while ventilation zones are mapped exactly where you heat up.',
 'Medium support\nSeamless knit construction\nMapped ventilation zones\nRemovable pads\nRacerback design',
 'women', 'training', 34990.00, NULL, NULL, 0),

(18, 2, 'Aero Shield Jacket', 'aero-shield-jacket',
 'Wind on the forecast, not on your run. A featherweight wind-resistant shell that packs into its own pocket, with laser-cut vents to dump heat when you push the pace.',
 'Wind-resistant ripstop shell\nPacks into chest pocket\nLaser-cut back vents\n360-degree reflective details\nWeight: 96g (size S)',
 'women', 'running', 89990.00, 69990.00, NULL, 0),

(19, 2, 'Everyday Jogger', 'everyday-jogger',
 'The jogger that gets everything right: tapered but not tight, soft but structured, smart enough for coffee and comfortable enough for the sofa.',
 'Tapered fit with ribbed cuffs\nBrushed-back French terry\nZippered side pockets\nDrawcord waistband\n70% cotton, 30% recycled polyester',
 'men', 'lifestyle', 54990.00, NULL, NULL, 0),

(20, 2, 'Flex Warm-Up Crew', 'flex-warm-up-crew',
 'Warm-ups, cool-downs and the bus ride home. A soft stretch crew for kids that layers over anything and survives every wash on their schedule.',
 'Soft stretch jersey\nRaglan sleeves for movement\nRibbed collar and cuffs\nEasy-care fabric\nAges 6 to 14',
 'kids', 'training', 34990.00, NULL, NULL, 0),

(21, 2, 'Rally Track Top', 'rally-track-top',
 'A heritage track silhouette re-cut for now. Contrast piping, a two-way front zip and a boxy fit that works over a hoodie or under a coat.',
 'Recycled tricot fabric\nContrast piping\nTwo-way front zip\nBoxy retro fit\nZippered hand pockets',
 'unisex', 'lifestyle', 69990.00, NULL, 'just-in', 0),

(22, 3, 'Endurance Run Cap', 'endurance-run-cap',
 'Five panels, forty grams, zero excuses. Quick-dry fabric, a sweat-wicking inner band and a foldable brim so it packs into any pocket.',
 'Quick-dry ripstop fabric\nSweat-wicking inner band\nFoldable soft brim\nAdjustable strap\nReflective front logo',
 'unisex', 'running', 22990.00, NULL, NULL, 0),

(23, 3, 'Session Gym Duffel', 'session-gym-duffel',
 'Everything in its place, from wet kit to laptop. A 35-litre duffel with a ventilated shoe tunnel, padded 15in sleeve and a water-resistant base that shrugs off gym floors.',
 '35L capacity\nVentilated shoe compartment\nPadded 15in laptop sleeve\nWater-resistant base\nDetachable shoulder strap',
 'unisex', 'training', 44990.00, NULL, NULL, 0),

(24, 3, 'Grip Crew Socks (3-Pack)', 'grip-crew-socks',
 'The socks your trainers deserve. Arch compression, a cushioned footbed and silicone grip zones that stop in-shoe slip during explosive work.',
 '3 pairs per pack\nArch compression band\nCushioned footbed\nSilicone heel grip\n68% cotton, 29% nylon, 3% elastane',
 'unisex', 'training', 14990.00, NULL, NULL, 0);

-- ---------------------------------------------------------------- product_images
-- Five images per product. sort_order 0 is the main card image; the rest
-- populate the card's thumbnail row (hover a thumb to swap the main image)
-- and the PDP gallery. All hotlinked from Unsplash — see the note at the top.
INSERT INTO product_images (product_id, url, alt, color, sort_order) VALUES
-- 1 Velocity Runner — Crimson Volt
(1, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=900&q=80', 'Velocity Runner in Crimson Volt, side profile', 'Crimson Volt', 0),
(1, 'https://images.unsplash.com/photo-1552674605-db6ffd4facb5?w=900&q=80', 'Runner mid-stride wearing the Velocity Runner', 'Crimson Volt', 1),
(1, 'https://images.unsplash.com/photo-1600185365483-26d7a4cc7519?w=900&q=80', 'Velocity Runner worn on foot, street view', 'Crimson Volt', 2),
(1, 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?w=900&q=80', 'Track session in the Velocity Runner', 'Crimson Volt', 3),
(1, 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=900&q=80', 'Velocity Runner pair, angled view', 'Crimson Volt', 4),
-- 2 Stride Pulse 2 — Glacier White
(2, 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=900&q=80', 'Stride Pulse 2 in Glacier White, angled view', 'Glacier White', 0),
(2, 'https://images.unsplash.com/photo-1434682881908-b43d0467b798?w=900&q=80', 'Athlete running on track in the Stride Pulse 2', 'Glacier White', 1),
(2, 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=900&q=80', 'Studio session in the Stride Pulse 2', 'Glacier White', 2),
(2, 'https://images.unsplash.com/photo-1600185365483-26d7a4cc7519?w=900&q=80', 'Stride Pulse 2 worn on foot', 'Glacier White', 3),
(2, 'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=900&q=80', 'Stride Pulse 2 clean side profile', 'Glacier White', 4),
-- 3 Apex Court Pro — Ink Black
(3, 'https://images.unsplash.com/photo-1546519638-68e109498ffc?w=900&q=80', 'Apex Court Pro on an outdoor basketball court', 'Ink Black', 0),
(3, 'https://images.unsplash.com/photo-1519861531473-9200262188bf?w=900&q=80', 'Player rising for a dunk in the Apex Court Pro', 'Ink Black', 1),
(3, 'https://images.unsplash.com/photo-1605348532760-6753d2c43329?w=900&q=80', 'Apex Court Pro pair, side view', 'Ink Black', 2),
(3, 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=900&q=80', 'Apex Court Pro on the hardwood', 'Ink Black', 3),
(3, 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=900&q=80', 'Apex Court Pro top-down view', 'Ink Black', 4),
-- 4 Ignite Trainer — Off-White
(4, 'https://images.unsplash.com/photo-1605348532760-6753d2c43329?w=900&q=80', 'Ignite Trainer in Off-White, pair view', 'Off-White', 0),
(4, 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=900&q=80', 'Lifter training in the Ignite Trainer', 'Off-White', 1),
(4, 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=900&q=80', 'Ignite Trainer angled profile', 'Off-White', 2),
(4, 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=900&q=80', 'Circuit training in the Ignite Trainer', 'Off-White', 3),
(4, 'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=900&q=80', 'Ignite Trainer side profile', 'Off-White', 4),
-- 5 Kinetic Flow — Rose Quartz
(5, 'https://images.unsplash.com/photo-1600185365483-26d7a4cc7519?w=900&q=80', 'Kinetic Flow worn on foot, street view', 'Rose Quartz', 0),
(5, 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=900&q=80', 'Studio training session in the Kinetic Flow', 'Rose Quartz', 1),
(5, 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=900&q=80', 'Kinetic Flow pair, angled view', 'Rose Quartz', 2),
(5, 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?w=900&q=80', 'Kinetic Flow on the move', 'Rose Quartz', 3),
(5, 'https://images.unsplash.com/photo-1525966222134-fcfa99b8ae77?w=900&q=80', 'Kinetic Flow sole detail', 'Rose Quartz', 4),
-- 6 Pace Chaser Trail — Storm Blue
(6, 'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=900&q=80', 'Pace Chaser Trail in Storm Blue, side profile', 'Storm Blue', 0),
(6, 'https://images.unsplash.com/photo-1483721310020-03333e577078?w=900&q=80', 'Trail shoes on rocky terrain', 'Storm Blue', 1),
(6, 'https://images.unsplash.com/photo-1600185365483-26d7a4cc7519?w=900&q=80', 'Pace Chaser Trail worn on foot', 'Storm Blue', 2),
(6, 'https://images.unsplash.com/photo-1552674605-db6ffd4facb5?w=900&q=80', 'Running the trail in the Pace Chaser', 'Storm Blue', 3),
(6, 'https://images.unsplash.com/photo-1605348532760-6753d2c43329?w=900&q=80', 'Pace Chaser Trail pair, side view', 'Storm Blue', 4),
-- 7 Metro Ease — Classic White
(7, 'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=900&q=80', 'Metro Ease in Classic White, side profile', 'Classic White', 0),
(7, 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=900&q=80', 'Metro Ease pair on concrete', 'Classic White', 1),
(7, 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=900&q=80', 'Metro Ease angled view', 'Classic White', 2),
(7, 'https://images.unsplash.com/photo-1560769629-975ec94e6a86?w=900&q=80', 'Metro Ease single shoe, studio', 'Classic White', 3),
(7, 'https://images.unsplash.com/photo-1525966222134-fcfa99b8ae77?w=900&q=80', 'Metro Ease sole detail', 'Classic White', 4),
-- 8 Halo Glide — Desert Sand
(8, 'https://images.unsplash.com/photo-1560769629-975ec94e6a86?w=900&q=80', 'Halo Glide in Desert Sand, single shoe', 'Desert Sand', 0),
(8, 'https://images.unsplash.com/photo-1525966222134-fcfa99b8ae77?w=900&q=80', 'Halo Glide sole detail', 'Desert Sand', 1),
(8, 'https://images.unsplash.com/photo-1600185365483-26d7a4cc7519?w=900&q=80', 'Halo Glide worn on foot', 'Desert Sand', 2),
(8, 'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=900&q=80', 'Halo Glide side profile', 'Desert Sand', 3),
(8, 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=900&q=80', 'Halo Glide top-down view', 'Desert Sand', 4),
-- 9 First Touch FG — Ink Black
(9, 'https://images.unsplash.com/photo-1574629810360-7efbbe195018?w=900&q=80', 'First Touch FG boot on the ball', 'Ink Black', 0),
(9, 'https://images.unsplash.com/photo-1511886929837-354d827aae26?w=900&q=80', 'Match play in the First Touch FG', 'Ink Black', 1),
(9, 'https://images.unsplash.com/photo-1546519638-68e109498ffc?w=900&q=80', 'First Touch FG on the pitch surface', 'Ink Black', 2),
(9, 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?w=900&q=80', 'Warm-up in the First Touch FG', 'Ink Black', 3),
(9, 'https://images.unsplash.com/photo-1605348532760-6753d2c43329?w=900&q=80', 'First Touch FG pair, side view', 'Ink Black', 4),
-- 10 Skyline Jr — Navy Pop
(10, 'https://images.unsplash.com/photo-1595341888016-a392ef81b7de?w=900&q=80', 'Skyline Jr kids shoe, angled view', 'Navy Pop', 0),
(10, 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=900&q=80', 'Skyline Jr pair, top view', 'Navy Pop', 1),
(10, 'https://images.unsplash.com/photo-1600185365483-26d7a4cc7519?w=900&q=80', 'Skyline Jr worn on foot', 'Navy Pop', 2),
(10, 'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=900&q=80', 'Skyline Jr side profile', 'Navy Pop', 3),
(10, 'https://images.unsplash.com/photo-1560769629-975ec94e6a86?w=900&q=80', 'Skyline Jr single shoe, studio', 'Navy Pop', 4),
-- 11 Sprint Spark Jr — Volt Green
(11, 'https://images.unsplash.com/photo-1600185365483-26d7a4cc7519?w=900&q=80', 'Sprint Spark Jr on foot', 'Volt Green', 0),
(11, 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?w=900&q=80', 'Kids sprinting on a track', 'Volt Green', 1),
(11, 'https://images.unsplash.com/photo-1595341888016-a392ef81b7de?w=900&q=80', 'Sprint Spark Jr angled view', 'Volt Green', 2),
(11, 'https://images.unsplash.com/photo-1552674605-db6ffd4facb5?w=900&q=80', 'Running in the Sprint Spark Jr', 'Volt Green', 3),
(11, 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=900&q=80', 'Sprint Spark Jr pair, studio', 'Volt Green', 4),
-- 12 Baseline Lo — Bone
(12, 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=900&q=80', 'Baseline Lo in Bone, angled view', 'Bone', 0),
(12, 'https://images.unsplash.com/photo-1518063319789-7217e6706b04?w=900&q=80', 'Baseline Lo courtside', 'Bone', 1),
(12, 'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=900&q=80', 'Baseline Lo side profile', 'Bone', 2),
(12, 'https://images.unsplash.com/photo-1560769629-975ec94e6a86?w=900&q=80', 'Baseline Lo single shoe, studio', 'Bone', 3),
(12, 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=900&q=80', 'Baseline Lo top-down view', 'Bone', 4),
-- 13 Core Performance Tee — White
(13, 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=900&q=80', 'Core Performance Tee in White, flat lay', 'White', 0),
(13, 'https://images.unsplash.com/photo-1503341504253-dff4815485f1?w=900&q=80', 'Core Performance Tee worn outdoors', 'White', 1),
(13, 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=900&q=80', 'Core Performance Tee layered look', 'White', 2),
(13, 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=900&q=80', 'Training in the Core Performance Tee', 'White', 3),
(13, 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=900&q=80', 'Studio session in the Core Performance Tee', 'White', 4),
-- 14 Tempo Run Shorts — Ink Black
(14, 'https://images.unsplash.com/photo-1552674605-db6ffd4facb5?w=900&q=80', 'Runner wearing the Tempo Run Shorts', 'Ink Black', 0),
(14, 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?w=900&q=80', 'Track session in the Tempo Run Shorts', 'Ink Black', 1),
(14, 'https://images.unsplash.com/photo-1434682881908-b43d0467b798?w=900&q=80', 'Tempo Run Shorts on a morning run', 'Ink Black', 2),
(14, 'https://images.unsplash.com/photo-1600185365483-26d7a4cc7519?w=900&q=80', 'Tempo Run Shorts, stride view', 'Ink Black', 3),
(14, 'https://images.unsplash.com/photo-1503341504253-dff4815485f1?w=900&q=80', 'Tempo Run Shorts styled off-duty', 'Ink Black', 4),
-- 15 Foundation Hoodie — Heather Grey
(15, 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=900&q=80', 'Foundation Hoodie in Heather Grey, worn', 'Heather Grey', 0),
(15, 'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?w=900&q=80', 'Foundation Hoodie folded, detail view', 'Heather Grey', 1),
(15, 'https://images.unsplash.com/photo-1503341504253-dff4815485f1?w=900&q=80', 'Foundation Hoodie styled outdoors', 'Heather Grey', 2),
(15, 'https://images.unsplash.com/photo-1552374196-1ab2a1c593e8?w=900&q=80', 'Foundation Hoodie casual look', 'Heather Grey', 3),
(15, 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=900&q=80', 'Foundation Hoodie layered', 'Heather Grey', 4),
-- 16 Momentum Leggings — Ink Black
(16, 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?w=900&q=80', 'Momentum Leggings during training', 'Ink Black', 0),
(16, 'https://images.unsplash.com/photo-1518611012118-696072aa579a?w=900&q=80', 'Squat session in the Momentum Leggings', 'Ink Black', 1),
(16, 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=900&q=80', 'Studio work in the Momentum Leggings', 'Ink Black', 2),
(16, 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?w=900&q=80', 'Momentum Leggings on the track', 'Ink Black', 3),
(16, 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=900&q=80', 'Momentum Leggings at the gym', 'Ink Black', 4),
-- 17 Balance Seamless Bra — Slate
(17, 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=900&q=80', 'Balance Seamless Bra in a studio session', 'Slate', 0),
(17, 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?w=900&q=80', 'Training in the Balance Seamless Bra', 'Slate', 1),
(17, 'https://images.unsplash.com/photo-1518611012118-696072aa579a?w=900&q=80', 'Balance Seamless Bra during a workout', 'Slate', 2),
(17, 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=900&q=80', 'Balance Seamless Bra at the gym', 'Slate', 3),
(17, 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?w=900&q=80', 'Balance Seamless Bra on the move', 'Slate', 4),
-- 18 Aero Shield Jacket — Signal Red
(18, 'https://images.unsplash.com/photo-1434682881908-b43d0467b798?w=900&q=80', 'Aero Shield Jacket on a morning run', 'Signal Red', 0),
(18, 'https://images.unsplash.com/photo-1552674605-db6ffd4facb5?w=900&q=80', 'Aero Shield Jacket at pace', 'Signal Red', 1),
(18, 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?w=900&q=80', 'Aero Shield Jacket on the track', 'Signal Red', 2),
(18, 'https://images.unsplash.com/photo-1600185365483-26d7a4cc7519?w=900&q=80', 'Aero Shield Jacket, stride view', 'Signal Red', 3),
(18, 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=900&q=80', 'Aero Shield Jacket styled', 'Signal Red', 4),
-- 19 Everyday Jogger — Charcoal
(19, 'https://images.unsplash.com/photo-1552374196-1ab2a1c593e8?w=900&q=80', 'Everyday Jogger styled with trainers', 'Charcoal', 0),
(19, 'https://images.unsplash.com/photo-1503341504253-dff4815485f1?w=900&q=80', 'Everyday Jogger, casual look', 'Charcoal', 1),
(19, 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=900&q=80', 'Everyday Jogger with a hoodie', 'Charcoal', 2),
(19, 'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?w=900&q=80', 'Everyday Jogger fabric detail', 'Charcoal', 3),
(19, 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=900&q=80', 'Everyday Jogger off-duty', 'Charcoal', 4),
-- 20 Flex Warm-Up Crew — Royal Blue
(20, 'https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?w=900&q=80', 'Flex Warm-Up Crew on a young athlete', 'Royal Blue', 0),
(20, 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?w=900&q=80', 'Kids warming up on the track', 'Royal Blue', 1),
(20, 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=900&q=80', 'Flex Warm-Up Crew layered', 'Royal Blue', 2),
(20, 'https://images.unsplash.com/photo-1503341504253-dff4815485f1?w=900&q=80', 'Flex Warm-Up Crew casual look', 'Royal Blue', 3),
(20, 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=900&q=80', 'Flex Warm-Up Crew in the studio', 'Royal Blue', 4),
-- 21 Rally Track Top — Forest Stripe
(21, 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=900&q=80', 'Rally Track Top street styled', 'Forest Stripe', 0),
(21, 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=900&q=80', 'Rally Track Top layered look', 'Forest Stripe', 1),
(21, 'https://images.unsplash.com/photo-1552374196-1ab2a1c593e8?w=900&q=80', 'Rally Track Top with joggers', 'Forest Stripe', 2),
(21, 'https://images.unsplash.com/photo-1503341504253-dff4815485f1?w=900&q=80', 'Rally Track Top casual look', 'Forest Stripe', 3),
(21, 'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?w=900&q=80', 'Rally Track Top fabric detail', 'Forest Stripe', 4),
-- 22 Endurance Run Cap — Sun Gold
(22, 'https://images.unsplash.com/photo-1556306535-0f09a537f0a3?w=900&q=80', 'Endurance Run Cap in Sun Gold', 'Sun Gold', 0),
(22, 'https://images.unsplash.com/photo-1521369909029-2afed882baee?w=900&q=80', 'Endurance Run Cap worn outdoors', 'Sun Gold', 1),
(22, 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?w=900&q=80', 'Endurance Run Cap on the track', 'Sun Gold', 2),
(22, 'https://images.unsplash.com/photo-1552674605-db6ffd4facb5?w=900&q=80', 'Endurance Run Cap on a run', 'Sun Gold', 3),
(22, 'https://images.unsplash.com/photo-1600185365483-26d7a4cc7519?w=900&q=80', 'Endurance Run Cap, side view', 'Sun Gold', 4),
-- 23 Session Gym Duffel — Ink Black
(23, 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=900&q=80', 'Session Gym Duffel, front view', 'Ink Black', 0),
(23, 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=900&q=80', 'Session Gym Duffel at the gym', 'Ink Black', 1),
(23, 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=900&q=80', 'Session Gym Duffel in the studio', 'Ink Black', 2),
(23, 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=900&q=80', 'Session Gym Duffel packed', 'Ink Black', 3),
(23, 'https://images.unsplash.com/photo-1605348532760-6753d2c43329?w=900&q=80', 'Session Gym Duffel with kit', 'Ink Black', 4),
-- 24 Grip Crew Socks — White Mix
(24, 'https://images.unsplash.com/photo-1586350977771-b3b0abd50c82?w=900&q=80', 'Grip Crew Socks 3-pack', 'White Mix', 0),
(24, 'https://images.unsplash.com/photo-1526506118085-60ce8714f8c5?w=900&q=80', 'Training session wearing Grip Crew Socks', 'White Mix', 1),
(24, 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=900&q=80', 'Grip Crew Socks at the gym', 'White Mix', 2),
(24, 'https://images.unsplash.com/photo-1600185365483-26d7a4cc7519?w=900&q=80', 'Grip Crew Socks worn with trainers', 'White Mix', 3),
(24, 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=900&q=80', 'Grip Crew Socks in the studio', 'White Mix', 4);

-- ---------------------------------------------------------------- product_variants
-- Adult shoes: EU 40-11. Kids shoes: EU 32-4. Apparel: XS-XL (kids 6-14yr).
-- A few variants have stock 0 on purpose so we can build "sold out" states.
INSERT INTO product_variants (product_id, sku, color, size, stock) VALUES
-- 1 Velocity Runner — Crimson Volt
(1, 'BF-VELO-CRV-6',  'Crimson Volt', 'EU 40',  12), (1, 'BF-VELO-CRV-7',  'Crimson Volt', 'EU 41',  18),
(1, 'BF-VELO-CRV-8',  'Crimson Volt', 'EU 42',  25), (1, 'BF-VELO-CRV-9',  'Crimson Volt', 'EU 43',  20),
(1, 'BF-VELO-CRV-10', 'Crimson Volt', 'EU 44',  8), (1, 'BF-VELO-CRV-11', 'Crimson Volt', 'EU 45',  0),
-- 2 Stride Pulse 2 — Glacier White
(2, 'BF-STRP-GLW-4', 'Glacier White', 'EU 37', 10), (2, 'BF-STRP-GLW-5', 'Glacier White', 'EU 38', 16),
(2, 'BF-STRP-GLW-6', 'Glacier White', 'EU 40', 22), (2, 'BF-STRP-GLW-7', 'Glacier White', 'EU 41', 14),
(2, 'BF-STRP-GLW-8', 'Glacier White', 'EU 42',  6),
-- 3 Apex Court Pro — Ink Black
(3, 'BF-APEX-INK-7',  'Ink Black', 'EU 41',  9), (3, 'BF-APEX-INK-8',  'Ink Black', 'EU 42', 15),
(3, 'BF-APEX-INK-9',  'Ink Black', 'EU 43', 17), (3, 'BF-APEX-INK-10', 'Ink Black', 'EU 44', 11),
(3, 'BF-APEX-INK-11', 'Ink Black', 'EU 45', 5), (3, 'BF-APEX-INK-12', 'Ink Black', 'EU 46', 3),
-- 4 Ignite Trainer — Off-White
(4, 'BF-IGNT-OFW-7',  'Off-White', 'EU 41',  7), (4, 'BF-IGNT-OFW-8',  'Off-White', 'EU 42', 13),
(4, 'BF-IGNT-OFW-9',  'Off-White', 'EU 43', 19), (4, 'BF-IGNT-OFW-10', 'Off-White', 'EU 44', 0),
(4, 'BF-IGNT-OFW-11', 'Off-White', 'EU 45', 4),
-- 5 Kinetic Flow — Rose Quartz
(5, 'BF-KINF-ROQ-4', 'Rose Quartz', 'EU 37', 11), (5, 'BF-KINF-ROQ-5', 'Rose Quartz', 'EU 38', 20),
(5, 'BF-KINF-ROQ-6', 'Rose Quartz', 'EU 40', 18), (5, 'BF-KINF-ROQ-7', 'Rose Quartz', 'EU 41',  9),
(5, 'BF-KINF-ROQ-8', 'Rose Quartz', 'EU 42',  2),
-- 6 Pace Chaser Trail — Storm Blue (limited drop: low stock everywhere)
(6, 'BF-PACE-STB-7',  'Storm Blue', 'EU 41',  3), (6, 'BF-PACE-STB-8',  'Storm Blue', 'EU 42',  4),
(6, 'BF-PACE-STB-9',  'Storm Blue', 'EU 43',  2), (6, 'BF-PACE-STB-10', 'Storm Blue', 'EU 44', 0),
(6, 'BF-PACE-STB-11', 'Storm Blue', 'EU 45', 1),
-- 7 Metro Ease — Classic White
(7, 'BF-METR-CLW-5',  'Classic White', 'EU 38', 14), (7, 'BF-METR-CLW-6',  'Classic White', 'EU 40', 21),
(7, 'BF-METR-CLW-7',  'Classic White', 'EU 41', 30), (7, 'BF-METR-CLW-8',  'Classic White', 'EU 42', 27),
(7, 'BF-METR-CLW-9',  'Classic White', 'EU 43', 24), (7, 'BF-METR-CLW-10', 'Classic White', 'EU 44', 16),
-- 8 Halo Glide — Desert Sand
(8, 'BF-HALO-DES-4', 'Desert Sand', 'EU 37',  8), (8, 'BF-HALO-DES-5', 'Desert Sand', 'EU 38', 12),
(8, 'BF-HALO-DES-6', 'Desert Sand', 'EU 40', 10), (8, 'BF-HALO-DES-7', 'Desert Sand', 'EU 41',  0),
(8, 'BF-HALO-DES-8', 'Desert Sand', 'EU 42',  5),
-- 9 First Touch FG — Ink Black
(9, 'BF-FTFG-INK-7',  'Ink Black', 'EU 41', 10), (9, 'BF-FTFG-INK-8',  'Ink Black', 'EU 42', 14),
(9, 'BF-FTFG-INK-9',  'Ink Black', 'EU 43', 12), (9, 'BF-FTFG-INK-10', 'Ink Black', 'EU 44', 7),
(9, 'BF-FTFG-INK-11', 'Ink Black', 'EU 45', 3),
-- 10 Skyline Jr — Navy Pop
(10, 'BF-SKYJ-NVP-13', 'Navy Pop', 'EU 32', 9), (10, 'BF-SKYJ-NVP-1', 'Navy Pop', 'EU 33', 13),
(10, 'BF-SKYJ-NVP-2',  'Navy Pop', 'EU 34', 15), (10, 'BF-SKYJ-NVP-3', 'Navy Pop', 'EU 35', 11),
(10, 'BF-SKYJ-NVP-4',  'Navy Pop', 'EU 37',  6),
-- 11 Sprint Spark Jr — Volt Green
(11, 'BF-SPSJ-VLG-13', 'Volt Green', 'EU 32', 12), (11, 'BF-SPSJ-VLG-1', 'Volt Green', 'EU 33', 17),
(11, 'BF-SPSJ-VLG-2',  'Volt Green', 'EU 34', 14), (11, 'BF-SPSJ-VLG-3', 'Volt Green', 'EU 35',  0),
(11, 'BF-SPSJ-VLG-4',  'Volt Green', 'EU 37',  8),
-- 12 Baseline Lo — Bone
(12, 'BF-BASL-BON-4', 'Bone', 'EU 37',  7), (12, 'BF-BASL-BON-5', 'Bone', 'EU 38', 11),
(12, 'BF-BASL-BON-6', 'Bone', 'EU 40', 13), (12, 'BF-BASL-BON-7', 'Bone', 'EU 41',  9),
(12, 'BF-BASL-BON-8', 'Bone', 'EU 42',  4),
-- 13 Core Performance Tee — White
(13, 'BF-CPTE-WHT-XS', 'White', 'XS', 15), (13, 'BF-CPTE-WHT-S', 'White', 'S', 28),
(13, 'BF-CPTE-WHT-M',  'White', 'M', 35), (13, 'BF-CPTE-WHT-L', 'White', 'L', 30),
(13, 'BF-CPTE-WHT-XL', 'White', 'XL', 18),
-- 14 Tempo Run Shorts — Ink Black
(14, 'BF-TMPO-INK-S', 'Ink Black', 'S', 20), (14, 'BF-TMPO-INK-M', 'Ink Black', 'M', 26),
(14, 'BF-TMPO-INK-L', 'Ink Black', 'L', 22), (14, 'BF-TMPO-INK-XL', 'Ink Black', 'XL', 9),
-- 15 Foundation Hoodie — Heather Grey
(15, 'BF-FNDH-HGR-XS', 'Heather Grey', 'XS', 10), (15, 'BF-FNDH-HGR-S', 'Heather Grey', 'S', 24),
(15, 'BF-FNDH-HGR-M',  'Heather Grey', 'M', 31), (15, 'BF-FNDH-HGR-L', 'Heather Grey', 'L', 26),
(15, 'BF-FNDH-HGR-XL', 'Heather Grey', 'XL', 0),
-- 16 Momentum Leggings — Ink Black
(16, 'BF-MOML-INK-XS', 'Ink Black', 'XS', 17), (16, 'BF-MOML-INK-S', 'Ink Black', 'S', 29),
(16, 'BF-MOML-INK-M',  'Ink Black', 'M', 33), (16, 'BF-MOML-INK-L', 'Ink Black', 'L', 21),
(16, 'BF-MOML-INK-XL', 'Ink Black', 'XL', 12),
-- 17 Balance Seamless Bra — Slate
(17, 'BF-BALB-SLT-XS', 'Slate', 'XS', 13), (17, 'BF-BALB-SLT-S', 'Slate', 'S', 19),
(17, 'BF-BALB-SLT-M',  'Slate', 'M', 23), (17, 'BF-BALB-SLT-L', 'Slate', 'L', 15),
(17, 'BF-BALB-SLT-XL', 'Slate', 'XL', 7),
-- 18 Aero Shield Jacket — Signal Red
(18, 'BF-AERO-SGR-XS', 'Signal Red', 'XS', 6), (18, 'BF-AERO-SGR-S', 'Signal Red', 'S', 14),
(18, 'BF-AERO-SGR-M',  'Signal Red', 'M', 16), (18, 'BF-AERO-SGR-L', 'Signal Red', 'L', 10),
(18, 'BF-AERO-SGR-XL', 'Signal Red', 'XL', 3),
-- 19 Everyday Jogger — Charcoal
(19, 'BF-EVJG-CHC-S', 'Charcoal', 'S', 18), (19, 'BF-EVJG-CHC-M', 'Charcoal', 'M', 27),
(19, 'BF-EVJG-CHC-L', 'Charcoal', 'L', 25), (19, 'BF-EVJG-CHC-XL', 'Charcoal', 'XL', 11),
-- 20 Flex Warm-Up Crew — Royal Blue (kids ages)
(20, 'BF-FLXC-RYB-6',  'Royal Blue', '6-7yr', 12), (20, 'BF-FLXC-RYB-8', 'Royal Blue', '8-9yr', 16),
(20, 'BF-FLXC-RYB-10', 'Royal Blue', '10-11yr', 14), (20, 'BF-FLXC-RYB-12', 'Royal Blue', '12-14yr', 9),
-- 21 Rally Track Top — Forest Stripe
(21, 'BF-RLLY-FST-S', 'Forest Stripe', 'S', 11), (21, 'BF-RLLY-FST-M', 'Forest Stripe', 'M', 15),
(21, 'BF-RLLY-FST-L', 'Forest Stripe', 'L', 13), (21, 'BF-RLLY-FST-XL', 'Forest Stripe', 'XL', 6),
-- 22-24 Accessories
(22, 'BF-ENDC-SNG-OS', 'Sun Gold',  'One Size', 40),
(23, 'BF-SESD-INK-OS', 'Ink Black', 'One Size', 25),
(24, 'BF-GRPS-WHM-S',  'White Mix', 'S', 30), (24, 'BF-GRPS-WHM-M', 'White Mix', 'M', 45),
(24, 'BF-GRPS-WHM-L',  'White Mix', 'L', 38);

-- ---------------------------------------------------------------- coupons
INSERT INTO coupons (id, code, type, value, min_spend, max_uses, uses, expires_at, is_active) VALUES
(1, 'WELCOME10', 'percent',       10.00,  0.00,  NULL, 42, '2027-12-31 23:59:59', 1),
(2, 'FIRSTKIT',  'fixed',         15000.00, 100000.00, 500,  12, '2026-12-31 23:59:59', 1),
(3, 'FREESHIP',  'free_shipping',  0.00,  50000.00, NULL, 87, NULL,                  1);

-- ---------------------------------------------------------------- campaigns
INSERT INTO campaigns (title, subtitle, cta_text, cta_url, image_url, sort_order, is_active) VALUES
('Move First.', 'The Velocity Runner is back in Crimson Volt. Engineered for the ones who set the pace.', 'Shop Running', '/sport/running', 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?w=1920&q=80', 1, 1),
('Own The Court.', 'Apex Court Pro. Low to the floor, first to the rim.', 'Shop Basketball', '/sport/basketball', 'https://images.unsplash.com/photo-1546519638-68e109498ffc?w=1920&q=80', 2, 1),
('Rain Won\'t Wait.', 'The Aero Shield Jacket packs into its own pocket. Harmattan or downpour — no excuses left.', 'Shop Women', '/women', 'https://images.unsplash.com/photo-1434682881908-b43d0467b798?w=1920&q=80', 3, 1);

-- ---------------------------------------------------------------- orders
-- Three orders for Jordan (user 2) plus one guest order, so the admin area
-- and "order history" have real data. order_items look up variant ids by SKU
-- with a subquery so this file never depends on auto-increment numbers.
INSERT INTO orders (id, order_number, user_id, email, status, subtotal, discount, shipping_cost, tax, total, coupon_id, delivery_method,
                    ship_first_name, ship_last_name, ship_line1, ship_city, ship_postcode, ship_country, ship_phone, placed_at) VALUES
(1, 'BF-260712-K3M8', 2, 'customer@buyfirst.test', 'delivered', 194980.00, 19500.00, 0.00, 35100.00, 210580.00, 1, 'standard',
 'Jordan', 'Okafor', '12 Allen Avenue', 'Ikeja', '100271', 'NG', '+234 803 555 0123', '2026-07-12 10:24:00'),
(2, 'BF-260801-Q7T2', 2, 'customer@buyfirst.test', 'shipped', 94980.00, 0.00, 4990.00, 19990.00, 119960.00, NULL, 'express',
 'Jordan', 'Okafor', '12 Allen Avenue', 'Ikeja', '100271', 'NG', '+234 803 555 0123', '2026-08-01 18:03:00'),
(3, 'BF-260812-A1C9', 2, 'customer@buyfirst.test', 'paid', 64990.00, 0.00, 4990.00, 13990.00, 83970.00, NULL, 'standard',
 'Jordan', 'Okafor', '15 Adeola Odeku Street', 'Lagos', '101241', 'NG', NULL, '2026-08-12 09:41:00'),
(4, 'BF-260813-Z4R7', NULL, 'guest.shopper@example.com', 'pending', 129990.00, 0.00, 0.00, 26000.00, 155990.00, 3, 'standard',
 'Chinedu', 'Okoro', '22 Aminu Kano Crescent', 'Abuja', '900288', 'NG', NULL, '2026-08-13 08:15:00');

INSERT INTO order_items (order_id, variant_id, product_name, sku, color, size, unit_price, quantity, line_total) VALUES
(1, (SELECT id FROM product_variants WHERE sku = 'BF-VELO-CRV-9'),  'Velocity Runner', 'BF-VELO-CRV-9', 'Crimson Volt', 'EU 43', 129990.00, 1, 129990.00),
(1, (SELECT id FROM product_variants WHERE sku = 'BF-FNDH-HGR-M'),  'Foundation Hoodie', 'BF-FNDH-HGR-M', 'Heather Grey', 'M', 64990.00, 1, 64990.00),
(2, (SELECT id FROM product_variants WHERE sku = 'BF-MOML-INK-M'),  'Momentum Leggings', 'BF-MOML-INK-M', 'Ink Black', 'M', 59990.00, 1, 59990.00),
(2, (SELECT id FROM product_variants WHERE sku = 'BF-BALB-SLT-M'),  'Balance Seamless Bra', 'BF-BALB-SLT-M', 'Slate', 'M', 34990.00, 1, 34990.00),
(3, (SELECT id FROM product_variants WHERE sku = 'BF-FNDH-HGR-L'),  'Foundation Hoodie', 'BF-FNDH-HGR-L', 'Heather Grey', 'L', 64990.00, 1, 64990.00),
(4, (SELECT id FROM product_variants WHERE sku = 'BF-VELO-CRV-8'),  'Velocity Runner', 'BF-VELO-CRV-8', 'Crimson Volt', 'EU 42', 129990.00, 1, 129990.00);

INSERT INTO payments (order_id, provider, amount, currency, status, reference) VALUES
(1, 'card', 210580.00, 'NGN', 'succeeded', 'ch_seed_3NqL8w2eZvKYlo2C'),
(2, 'card', 119960.00, 'NGN', 'succeeded', 'ch_seed_3O1M9x2eZvKYlo2D'),
(3, 'card',  83970.00, 'NGN', 'succeeded', 'ch_seed_3O2N0y2eZvKYlo2E'),
(4, 'card', 155990.00, 'NGN', 'pending',   NULL);

INSERT INTO coupon_redemptions (coupon_id, order_id, user_id) VALUES
(1, 1, 2),
(3, 4, NULL);

-- ---------------------------------------------------------------- wishlist
-- Jordan already shops these — so the header heart is not empty on first login.
INSERT INTO wishlists (user_id, product_id) VALUES
(2, 1),
(2, 15),
(2, 3);

-- ---------------------------------------------------------------- reviews
INSERT INTO reviews (product_id, user_id, rating, title, body, is_verified_purchase, status, created_at) VALUES
(1, 2, 5, 'Best daily trainer I have owned', 'Three hundred kilometres in and the cushioning still feels new. Sizing is true — I am an EU 43 in everything and the 43 fits perfectly.', 1, 'approved', '2026-07-20 19:12:00'),
(1, 3, 4, 'Fast but snug in the toe box', 'Love the energy return on tempo days. If you have wide feet, consider going half a size up.', 0, 'approved', '2026-07-25 08:30:00'),
(7, 3, 5, 'Goes with everything', 'Wore them to work, then a gig, then a Sunday walk. Zero break-in period and they clean up easily.', 0, 'approved', '2026-06-30 14:45:00'),
(15, 2, 5, 'Heavyweight in the best way', 'This is a proper thick hoodie, not a thin printed thing. Washed five times, no shrinking, no bobbling.', 1, 'approved', '2026-07-18 21:05:00'),
(16, 2, 5, 'Actually squat-proof', 'Tested under gym lights — completely opaque. The waistband pocket fits a key and a card, which is all I need.', 1, 'approved', '2026-08-05 07:55:00'),
(16, 3, 4, 'Great fit, wish there were more colours', 'The high waist stays put through burpees. Would buy again in every colour if they existed.', 0, 'approved', '2026-08-07 12:20:00'),
(4, 3, 3, 'Good for lifting, average for cardio', 'Rock solid under a barbell but a bit firm on the treadmill. Know what you are buying it for.', 0, 'approved', '2026-08-02 17:40:00'),
(6, 3, 5, 'Grip for days', 'Took these through a muddy 15k trail race and never slipped once. Drainage after river crossings is genuinely impressive.', 0, 'approved', '2026-08-09 10:10:00'),
(2, 3, 5, 'The update the Pulse needed', 'Softer heel, roomier toes, same lightweight feel. My new half-marathon shoe.', 0, 'pending', '2026-08-11 16:25:00'),
(13, 2, 4, 'Solid basic', 'Wicks well and the longer back hem is a nice touch. Slightly boxy fit, which I like.', 1, 'pending', '2026-08-12 20:15:00');

-- ---------------------------------------------------------------- newsletter
INSERT INTO newsletter_subscribers (email, user_id) VALUES
('customer@buyfirst.test', 2),
('maya.reid@example.com', 3),
('running.fan.lagos@example.com', NULL);

-- CMS pages (help, legal, company) are HTML-heavy, so they live in
-- database/seed-pages.php and are upserted separately:
--   C:\xampp\php\php.exe database/seed-pages.php
