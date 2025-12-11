<?php
// Initialize SQLite database with default admin donor

$dbPath = __DIR__ . '/../database/donor.db';

// Create database directory if it doesn't exist
if (!is_dir(dirname($dbPath))) {
    mkdir(dirname($dbPath), 0755, true);
}

$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create donors table
$db->exec('
    CREATE TABLE IF NOT EXISTS donors (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        name TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL,
        phone TEXT,
        organization TEXT NOT NULL,
        profile_photo_path TEXT,
        wallet_balance REAL DEFAULT 0,
        loyalty_points INTEGER DEFAULT 0,
        donor_id_code TEXT UNIQUE NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
');

// Create donations table
$db->exec('
    CREATE TABLE IF NOT EXISTS donations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        donor_id INTEGER NOT NULL,
        project_id INTEGER,
        amount REAL NOT NULL,
        points_earned INTEGER DEFAULT 0,
        donation_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        status TEXT DEFAULT "completed",
        FOREIGN KEY(donor_id) REFERENCES donors(id)
    )
');

// Create projects table
$db->exec('
    CREATE TABLE IF NOT EXISTS projects (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        description TEXT,
        target_amount REAL NOT NULL,
        raised_amount REAL DEFAULT 0,
        progress_percentage REAL DEFAULT 0,
        image_path TEXT,
        category TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
');

// Create wallet transactions table
$db->exec('
    CREATE TABLE IF NOT EXISTS wallet_transactions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        donor_id INTEGER NOT NULL,
        transaction_type TEXT NOT NULL,
        amount REAL NOT NULL,
        description TEXT,
        transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(donor_id) REFERENCES donors(id)
    )
');

// Check if admin donor already exists
$stmt = $db->prepare('SELECT COUNT(*) as count FROM donors WHERE username = ?');
$stmt->execute(['admin']);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result['count'] == 0) {
    // Insert default admin donor
    $adminPassword = password_hash('admin123', PASSWORD_BCRYPT);
    $donorCode = 'DONOR-' . strtoupper(bin2hex(random_bytes(6)));
    
    $stmt = $db->prepare('
        INSERT INTO donors (username, password_hash, name, email, organization, donor_id_code)
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        'admin',
        $adminPassword,
        'Admin Donor',
        'admin@pearledu.fund',
        'Pearl Edu Fund',
        $donorCode
    ]);
    
    echo "✓ Database initialized successfully\n";
    echo "✓ Admin donor created with credentials: admin / admin123\n";
} else {
    echo "✓ Database already initialized\n";
}

// Insert sample projects if none exist
$stmt = $db->prepare('SELECT COUNT(*) as count FROM projects');
$stmt->execute();
$projectCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

if ($projectCount == 0) {
    $projects = [
        [
            'title' => "Children's Education Support",
            'description' => 'Provide scholarships and school supplies to children of non-teaching staff',
            'target_amount' => 120000000,
            'raised_amount' => 68000000,
            'category' => 'education'
        ],
        [
            'title' => 'Livelihood & Family Support',
            'description' => 'Programs that improve family resilience through savings groups and training',
            'target_amount' => 100000000,
            'raised_amount' => 45000000,
            'category' => 'livelihoods'
        ],
        [
            'title' => 'Mentorship Program',
            'description' => 'One-on-one mentoring and career guidance for underprivileged children',
            'target_amount' => 50000000,
            'raised_amount' => 22000000,
            'category' => 'mentorship'
        ]
    ];
    
    $stmt = $db->prepare('
        INSERT INTO projects (title, description, target_amount, raised_amount, progress_percentage, category)
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    
    foreach ($projects as $project) {
        $progress = ($project['raised_amount'] / $project['target_amount']) * 100;
        $stmt->execute([
            $project['title'],
            $project['description'],
            $project['target_amount'],
            $project['raised_amount'],
            round($progress, 2),
            $project['category']
        ]);
    }
    
    echo "✓ Sample projects created\n";
}
?>
