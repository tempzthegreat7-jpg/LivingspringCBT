<?php
/**
 * Seed script — creates admin, teacher, student accounts and exam questions.
 * Run via: DB_USER=debian-sys-maint DB_PASS=WJ6gXoupjEKbfKJP DB2_USER=debian-sys-maint DB2_PASS=WJ6gXoupjEKbfKJP php tests/seed.php
 */

require __DIR__ . '/../helpers.php';
require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$config2 = require basePath('config/config-db2.php');

$db = new Database($config);
$db2 = new Database($config2);

// --- 1. Ensure schemas ---
echo "[1] Ensuring schemas...\n";
adminEnsureTeacherUsersSchema($db);
adminEnsureStudentUsersSchema($db);
adminEnsureStudentClassLocksSchema($db);
adminEnsureNotificationsSchema($db);
ensureAssessmentConfigsSchema($db2);
ensureExamActivationSchema($db2);
ensureExamAttemptsSchema($db2);
ensureStudentExamSessionsSchema($db2);
echo "    Done.\n";

// --- 2. Create admin account ---
echo "[2] Creating admin account...\n";
$adminPassword = 'admin123';
$adminHash = password_hash($adminPassword, PASSWORD_BCRYPT);
$existing = $db->query("SELECT id FROM teacher_users WHERE LOWER(name) = 'admin user' LIMIT 1")->fetch();
if (!$existing) {
    $db->query(
        "INSERT INTO teacher_users (name, password, role, can_set_questions, is_active, can_manage_students, assigned_subjects, assigned_subject_categories)
         VALUES (:name, :password, 'admin', 1, 1, 1, 'english,mathematics,physics', :cats)",
        [
            'name' => 'Admin User',
            'password' => $adminHash,
            'cats' => '{"english":"both","mathematics":"both","physics":"both"}'
        ]
    );
    echo "    Admin created: 'Admin User' / password: {$adminPassword}\n";
} else {
    // Update password
    $db->query("UPDATE teacher_users SET password = :password WHERE id = :id", [
        'password' => $adminHash,
        'id' => (int) $existing['id']
    ]);
    echo "    Admin already exists (id={$existing['id']}), password updated.\n";
}

// --- 3. Create teacher account ---
echo "[3] Creating teacher account...\n";
$teacherPassword = 'teacher123';
$teacherHash = password_hash($teacherPassword, PASSWORD_BCRYPT);
$existing = $db->query("SELECT id FROM teacher_users WHERE LOWER(name) = 'mr. johnson' LIMIT 1")->fetch();
if (!$existing) {
    $db->query(
        "INSERT INTO teacher_users (name, password, role, can_set_questions, is_active, can_manage_students, assigned_subjects, assigned_subject_categories)
         VALUES (:name, :password, 'teacher', 1, 1, 0, 'mathematics', :cats)",
        [
            'name' => 'Mr. Johnson',
            'password' => $teacherHash,
            'cats' => '{"mathematics":"both"}'
        ]
    );
    echo "    Teacher created: 'Mr. Johnson' / password: {$teacherPassword}\n";
} else {
    echo "    Teacher already exists (id={$existing['id']}).\n";
}

// --- 4. Create student account ---
echo "[4] Creating student account...\n";
$studentPassword = 'student123';
$studentHash = password_hash($studentPassword, PASSWORD_BCRYPT);
$existing = $db->query("SELECT id FROM student_users WHERE student_name = 'Test Student' AND student_class = 'SS3' LIMIT 1")->fetch();
if (!$existing) {
    $db->query(
        "INSERT INTO student_users (student_name, student_class, password_hash, display_password, is_active)
         VALUES (:name, :class, :hash, :display, 1)",
        [
            'name' => 'Test Student',
            'class' => 'SS3',
            'hash' => $studentHash,
            'display' => $studentPassword
        ]
    );
    echo "    Student created: 'Test Student' (SS3) / password: {$studentPassword}\n";
} else {
    echo "    Student already exists (id={$existing['id']}).\n";
}

// --- 5. Seed exam questions (Mathematics SS3 1st Term) ---
echo "[5] Seeding exam questions in mathematics_ss3_first_term...\n";
$tableName = 'mathematics_ss3_first_term';
$db2->query("CREATE TABLE IF NOT EXISTS {$tableName} (
    number INT(11) NOT NULL PRIMARY KEY,
    question MEDIUMTEXT NOT NULL,
    choice1 MEDIUMTEXT NOT NULL,
    choice2 MEDIUMTEXT NOT NULL,
    choice3 MEDIUMTEXT NOT NULL,
    choice4 MEDIUMTEXT NOT NULL,
    correct_answer MEDIUMTEXT NOT NULL,
    image_path VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

$questions = [
    [1, 'What is 2 + 2?', '3', '4', '5', '6', '4'],
    [2, 'What is the square root of 144?', '10', '11', '12', '13', '12'],
    [3, 'Solve: 5x = 25', 'x = 3', 'x = 4', 'x = 5', 'x = 6', 'x = 5'],
    [4, 'What is 15% of 200?', '20', '25', '30', '35', '30'],
    [5, 'What is the value of π (to 2 decimal places)?', '3.12', '3.14', '3.16', '3.18', '3.14'],
];

foreach ($questions as $q) {
    $db2->query(
        "INSERT INTO {$tableName} (number, question, choice1, choice2, choice3, choice4, correct_answer)
         VALUES (:num, :question, :c1, :c2, :c3, :c4, :correct)
         ON DUPLICATE KEY UPDATE question = VALUES(question)",
        [
            'num' => $q[0],
            'question' => $q[1],
            'c1' => $q[2],
            'c2' => $q[3],
            'c3' => $q[4],
            'c4' => $q[5],
            'correct' => $q[6]
        ]
    );
}
echo "    5 questions seeded.\n";

// --- 6. Create assessment config for Mathematics SS3 exam ---
echo "[6] Creating assessment config...\n";
$existing = $db2->query("SELECT id FROM assessment_configs WHERE table_name = :tn LIMIT 1", ['tn' => $tableName])->fetch();
if (!$existing) {
    $db2->query(
        "INSERT INTO assessment_configs (teacher_user_id, subject, student_class, task_type, header_text, term_key, duration_seconds, question_limit, table_name)
         VALUES (1, 'mathematics', 'SS3', 'exam', '1st Term', 'first_term', 1800, 5, :tn)",
        ['tn' => $tableName]
    );
    echo "    Config created for Mathematics SS3 1st Term exam (30min, 5 questions).\n";
} else {
    echo "    Config already exists (id={$existing['id']}).\n";
}

// --- 7. Activate the exam ---
echo "[7] Activating Mathematics exam for SS3...\n";
$db2->query("CREATE TABLE IF NOT EXISTS exam_active_subjects (
    student_class VARCHAR(20) NOT NULL,
    subject VARCHAR(80) NOT NULL,
    activated_by INT(11) NULL,
    activated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (student_class, subject)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
$db2->query(
    "INSERT INTO exam_active_subjects (student_class, subject, activated_by)
     VALUES ('SS3', 'mathematics', 1)
     ON DUPLICATE KEY UPDATE activated_at = CURRENT_TIMESTAMP",
    []
);
echo "    Mathematics exam activated for SS3.\n";

echo "\n=== SEED COMPLETE ===\n";
echo "Admin:   'Admin User' / password: admin123\n";
echo "Teacher: 'Mr. Johnson' / password: teacher123\n";
echo "Student: 'Test Student' (SS3) / password: student123\n";
echo "Exam:    Mathematics SS3 1st Term (5 questions, 30min)\n";
