<?php

if (function_exists('adminEnsureTeacherUsersSchema')) {
    return;
}

function adminEnsureTeacherUsersSchema($db)
{
    // Create the table if it doesn't exist (fresh installs).
    $db->query("CREATE TABLE IF NOT EXISTS teacher_users (
        id INT(11) NOT NULL AUTO_INCREMENT,
        name VARCHAR(120) NOT NULL,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(20) NOT NULL DEFAULT 'teacher',
        can_set_questions TINYINT(1) NOT NULL DEFAULT 1,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        can_manage_students TINYINT(1) NOT NULL DEFAULT 0,
        assigned_subjects VARCHAR(255) NOT NULL DEFAULT 'english',
        assigned_subject_categories TEXT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        last_seen_at DATETIME NULL,
        failed_login_attempts INT(11) NOT NULL DEFAULT 0,
        lock_until DATETIME NULL,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    // Make sure required columns exist before using admin features.
    $columns = [];
    $describeRows = $db->query('DESCRIBE teacher_users')->fetchAll();

    foreach ($describeRows as $row) {
        $columns[$row['Field']] = true;
    }

    if (!isset($columns['role'])) {
        $db->query("ALTER TABLE teacher_users ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'teacher' AFTER password");
    }

    if (!isset($columns['can_set_questions'])) {
        $db->query('ALTER TABLE teacher_users ADD COLUMN can_set_questions TINYINT(1) NOT NULL DEFAULT 1 AFTER role');
    }

    if (!isset($columns['is_active'])) {
        $db->query('ALTER TABLE teacher_users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER can_set_questions');
    }

    if (!isset($columns['can_manage_students'])) {
        $db->query('ALTER TABLE teacher_users ADD COLUMN can_manage_students TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active');
    }

    if (!isset($columns['assigned_subjects'])) {
        $db->query("ALTER TABLE teacher_users ADD COLUMN assigned_subjects VARCHAR(255) NOT NULL DEFAULT 'english' AFTER can_manage_students");
    }

    if (!isset($columns['assigned_subject_categories'])) {
        $db->query("ALTER TABLE teacher_users ADD COLUMN assigned_subject_categories TEXT NULL AFTER assigned_subjects");
    }

    if (!isset($columns['created_at'])) {
        $db->query('ALTER TABLE teacher_users ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER assigned_subjects');
    }

    if (!isset($columns['last_seen_at'])) {
        $db->query('ALTER TABLE teacher_users ADD COLUMN last_seen_at DATETIME NULL AFTER created_at');
    }

    if (!isset($columns['failed_login_attempts'])) {
        $db->query('ALTER TABLE teacher_users ADD COLUMN failed_login_attempts INT(11) NOT NULL DEFAULT 0 AFTER last_seen_at');
    }

    if (!isset($columns['lock_until'])) {
        $db->query('ALTER TABLE teacher_users ADD COLUMN lock_until DATETIME NULL AFTER failed_login_attempts');
    }

    $db->query("UPDATE teacher_users SET role = 'teacher' WHERE role IS NULL OR TRIM(role) = ''");
    $db->query('UPDATE teacher_users SET can_set_questions = 1 WHERE can_set_questions IS NULL');
    $db->query('UPDATE teacher_users SET is_active = 1 WHERE is_active IS NULL');
    $db->query('UPDATE teacher_users SET can_manage_students = 0 WHERE can_manage_students IS NULL');
    $db->query("UPDATE teacher_users SET assigned_subjects = 'english' WHERE assigned_subjects IS NULL OR TRIM(assigned_subjects) = ''");
    $db->query("UPDATE teacher_users SET assigned_subject_categories = '{}' WHERE assigned_subject_categories IS NULL OR TRIM(assigned_subject_categories) = ''");
    $db->query('UPDATE teacher_users SET failed_login_attempts = 0 WHERE failed_login_attempts IS NULL OR failed_login_attempts < 0');

    // Keep at least one admin account in the system.
    $adminCountRow = $db->query("SELECT COUNT(*) AS total FROM teacher_users WHERE LOWER(role) = 'admin'")->fetch();
    $adminCount = (int) ($adminCountRow['total'] ?? 0);

    if ($adminCount === 0) {
        $firstUser = $db->query('SELECT id FROM teacher_users ORDER BY id ASC LIMIT 1')->fetch();

        if ($firstUser && isset($firstUser['id'])) {
            $db->query("UPDATE teacher_users SET role = 'admin' WHERE id = :id", ['id' => (int) $firstUser['id']]);
        }
    }

    adminEnsureSubjectsSchema($db);
    adminEnsureAuditLogsSchema($db);
    adminEnsureTeacherAlertsSchema($db);
    adminEnsureStudentUsersSchema($db);
}

function adminMarkUserSeen($db, $userId)
{
    // Save last seen time for online/offline display.
    $id = (int) $userId;
    if ($id <= 0) {
        return;
    }

    $db->query('UPDATE teacher_users SET last_seen_at = NOW() WHERE id = :id LIMIT 1', [
        'id' => $id
    ]);
}

function adminMarkUserOffline($db, $userId)
{
    // Clear last seen time on logout.
    $id = (int) $userId;
    if ($id <= 0) {
        return;
    }

    $db->query('UPDATE teacher_users SET last_seen_at = NULL WHERE id = :id LIMIT 1', [
        'id' => $id
    ]);
}

function adminVerifyPassword($rawPassword, $storedPassword)
{
    // Accept old plain passwords and new bcrypt passwords.
    if (Validation::match($rawPassword, $storedPassword)) {
        return true;
    }

    if (preg_match('/^\$2y\$/', $storedPassword)) {
        return password_verify($rawPassword, $storedPassword);
    }

    return false;
}

function adminNormalizeRole($role)
{
    $value = strtolower(trim((string) $role));
    return $value === 'admin' ? 'admin' : 'teacher';
}

function adminClassOptions()
{
    return ['JSS1', 'JSS2', 'JSS3', 'SS1', 'SS2', 'SS3'];
}

function adminNormalizeStudentClass($value, $default = 'SS3')
{
    $normalized = strtoupper(trim((string) $value));
    return in_array($normalized, adminClassOptions(), true) ? $normalized : strtoupper((string) $default);
}

function adminEnsureStudentUsersSchema($db)
{
    $db->query(
        'CREATE TABLE IF NOT EXISTS student_users (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            student_name VARCHAR(120) NOT NULL,
            student_class VARCHAR(20) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            display_password VARCHAR(120) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            active_session_token VARCHAR(128) NULL,
            active_session_seen_at DATETIME NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_student_login (student_name, student_class)
        )'
    );

    $columns = [];
    $describeRows = $db->query('DESCRIBE student_users')->fetchAll();
    foreach ($describeRows as $row) {
        $columns[(string) ($row['Field'] ?? '')] = true;
    }

    if (!isset($columns['display_password'])) {
        $db->query("ALTER TABLE student_users ADD COLUMN display_password VARCHAR(120) NOT NULL DEFAULT '' AFTER password_hash");
        $db->query("UPDATE student_users SET display_password = '' WHERE display_password IS NULL");
    }

    if (!isset($columns['is_active'])) {
        $db->query('ALTER TABLE student_users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER display_password');
    }

    if (!isset($columns['active_session_token'])) {
        $db->query('ALTER TABLE student_users ADD COLUMN active_session_token VARCHAR(128) NULL AFTER is_active');
    }

    if (!isset($columns['active_session_seen_at'])) {
        $db->query('ALTER TABLE student_users ADD COLUMN active_session_seen_at DATETIME NULL AFTER active_session_token');
    }

    if (!isset($columns['created_at'])) {
        $db->query('ALTER TABLE student_users ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER active_session_seen_at');
    }

    if (!isset($columns['updated_at'])) {
        $db->query('ALTER TABLE student_users ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at');
    }

    $indexes = $db->query('SHOW INDEX FROM student_users')->fetchAll();
    $hasUniqueLoginIndex = false;
    foreach ($indexes as $indexRow) {
        $keyName = strtolower((string) ($indexRow['Key_name'] ?? ''));
        if ($keyName === 'uniq_student_login') {
            $hasUniqueLoginIndex = true;
            break;
        }
    }

    if (!$hasUniqueLoginIndex) {
        $db->query('ALTER TABLE student_users ADD UNIQUE KEY uniq_student_login (student_name, student_class)');
    }

    $db->query("UPDATE student_users SET student_class = 'SS3' WHERE student_class IS NULL OR TRIM(student_class) = ''");
    $db->query("UPDATE student_users SET display_password = 'changeme123' WHERE display_password IS NULL OR TRIM(display_password) = ''");
    $db->query('UPDATE student_users SET is_active = 1 WHERE is_active IS NULL');
}

function adminEnsureStudentClassLocksSchema($db)
{
    $db->query(
        'CREATE TABLE IF NOT EXISTS student_class_locks (
            student_class VARCHAR(20) NOT NULL PRIMARY KEY,
            is_locked TINYINT(1) NOT NULL DEFAULT 0,
            updated_by_admin_id INT(11) NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )'
    );

    foreach (adminClassOptions() as $classLabel) {
        $db->query(
            'INSERT INTO student_class_locks (student_class, is_locked, updated_by_admin_id)
             VALUES (:student_class, 0, NULL)
             ON DUPLICATE KEY UPDATE student_class = student_class',
            ['student_class' => (string) $classLabel]
        );
    }
}

function adminEnsureMaintenanceModeSchema($db)
{
    $db->query(
        'CREATE TABLE IF NOT EXISTS app_maintenance_mode (
            id TINYINT(1) NOT NULL PRIMARY KEY,
            is_enabled TINYINT(1) NOT NULL DEFAULT 0,
            message VARCHAR(255) NOT NULL DEFAULT "We are updating the platform. Please check back shortly.",
            updated_by_admin_id INT(11) NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )'
    );

    $db->query(
        'INSERT INTO app_maintenance_mode (id, is_enabled, message, updated_by_admin_id)
         VALUES (1, 0, :message, NULL)
         ON DUPLICATE KEY UPDATE id = id',
        [
            'message' => 'We are updating the platform. Please check back shortly.'
        ]
    );
}

function adminGetMaintenanceMode($db)
{
    adminEnsureMaintenanceModeSchema($db);

    $row = $db->query(
        'SELECT id, is_enabled, message, updated_by_admin_id, updated_at
         FROM app_maintenance_mode
         WHERE id = 1
         LIMIT 1'
    )->fetch();

    return [
        'enabled' => (int) ($row['is_enabled'] ?? 0) === 1,
        'message' => trim((string) ($row['message'] ?? 'We are updating the platform. Please check back shortly.')),
        'updated_by_admin_id' => (int) ($row['updated_by_admin_id'] ?? 0),
        'updated_at' => (string) ($row['updated_at'] ?? '')
    ];
}

function adminSetMaintenanceMode($db, $isEnabled, $message, $adminUserId = null)
{
    adminEnsureMaintenanceModeSchema($db);

    $cleanMessage = trim((string) $message);
    if ($cleanMessage === '') {
        $cleanMessage = 'We are updating the platform. Please check back shortly.';
    }

    $db->query(
        'UPDATE app_maintenance_mode
         SET is_enabled = :is_enabled,
             message = :message,
             updated_by_admin_id = :updated_by_admin_id
         WHERE id = 1
         LIMIT 1',
        [
            'is_enabled' => $isEnabled ? 1 : 0,
            'message' => $cleanMessage,
            'updated_by_admin_id' => $adminUserId === null ? null : (int) $adminUserId
        ]
    );
}

function adminStudentClassLockMap($db)
{
    adminEnsureStudentClassLocksSchema($db);
    $rows = $db->query(
        'SELECT student_class, is_locked
         FROM student_class_locks
         ORDER BY student_class ASC'
    )->fetchAll();

    $map = [];
    foreach (adminClassOptions() as $classLabel) {
        $map[$classLabel] = false;
    }

    foreach ($rows as $row) {
        $classLabel = adminNormalizeStudentClass((string) ($row['student_class'] ?? 'SS3'));
        $map[$classLabel] = (int) ($row['is_locked'] ?? 0) === 1;
    }

    return $map;
}

function adminIsStudentClassLocked($db, $studentClass)
{
    $classLabel = adminNormalizeStudentClass($studentClass);
    $map = adminStudentClassLockMap($db);
    return !empty($map[$classLabel]);
}

function adminSetStudentClassLock($db, $studentClass, $isLocked, $adminUserId = null)
{
    adminEnsureStudentClassLocksSchema($db);
    $classLabel = adminNormalizeStudentClass($studentClass);

    $db->query(
        'INSERT INTO student_class_locks (student_class, is_locked, updated_by_admin_id)
         VALUES (:student_class, :is_locked, :updated_by_admin_id)
         ON DUPLICATE KEY UPDATE
            is_locked = VALUES(is_locked),
            updated_by_admin_id = VALUES(updated_by_admin_id)',
        [
            'student_class' => $classLabel,
            'is_locked' => (int) ($isLocked ? 1 : 0),
            'updated_by_admin_id' => $adminUserId === null ? null : (int) $adminUserId
        ]
    );
}

function adminStudentSessionTimeoutSeconds()
{
    return 120;
}

function adminStudentSessionIsActive(array $studentRow)
{
    $token = trim((string) ($studentRow['active_session_token'] ?? ''));
    $seenAtRaw = trim((string) ($studentRow['active_session_seen_at'] ?? ''));

    if ($token === '' || $seenAtRaw === '') {
        return false;
    }

    $seenAtTs = strtotime($seenAtRaw);
    if ($seenAtTs === false) {
        return false;
    }

    return (time() - $seenAtTs) <= adminStudentSessionTimeoutSeconds();
}

function adminMarkStudentSessionActive($db, $studentId, $sessionToken)
{
    $db->query(
        'UPDATE student_users
         SET active_session_token = :active_session_token,
             active_session_seen_at = NOW()
         WHERE id = :id
         LIMIT 1',
        [
            'active_session_token' => (string) $sessionToken,
            'id' => (int) $studentId
        ]
    );
}

function adminMarkStudentSessionSeen($db, $studentId, $sessionToken)
{
    $db->query(
        'UPDATE student_users
         SET active_session_seen_at = NOW()
         WHERE id = :id
           AND active_session_token = :active_session_token
         LIMIT 1',
        [
            'id' => (int) $studentId,
            'active_session_token' => (string) $sessionToken
        ]
    );
}

function adminClearStudentSession($db, $studentId, $sessionToken = null)
{
    $params = [
        'id' => (int) $studentId
    ];
    $where = 'WHERE id = :id';

    if ($sessionToken !== null) {
        $where .= ' AND active_session_token = :active_session_token';
        $params['active_session_token'] = (string) $sessionToken;
    }

    $db->query(
        'UPDATE student_users
         SET active_session_token = NULL,
             active_session_seen_at = NULL
         ' . $where . '
         LIMIT 1',
        $params
    );
}

function adminGenerateStudentPassword($length = 8)
{
    $pool = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
    $size = max(6, min(20, (int) $length));
    $max = strlen($pool) - 1;
    $password = '';

    for ($i = 0; $i < $size; $i++) {
        $password .= $pool[random_int(0, $max)];
    }

    return $password;
}

function adminEnsureSubjectsSchema($db)
{
    // Create table for custom subjects added by admins.
    $db->query(
        'CREATE TABLE IF NOT EXISTS custom_subjects (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            slug VARCHAR(80) NOT NULL UNIQUE,
            label VARCHAR(120) NOT NULL,
            category VARCHAR(20) NOT NULL DEFAULT "both",
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );

    $columns = $db->query('DESCRIBE custom_subjects')->fetchAll();
    $hasCategory = false;
    foreach ($columns as $column) {
        if (strtolower((string) ($column['Field'] ?? '')) === 'category') {
            $hasCategory = true;
            break;
        }
    }

    if (!$hasCategory) {
        $db->query('ALTER TABLE custom_subjects ADD COLUMN category VARCHAR(20) NOT NULL DEFAULT "both" AFTER label');
    }

    $db->query("UPDATE custom_subjects SET category = 'both' WHERE category IS NULL OR TRIM(category) = ''");
}

function adminAvailableSubjects($db = null)
{
    // English and Mathematics are always available.
    $subjects = [
        'english' => 'English',
        'mathematics' => 'Mathematics'
    ];

    if ($db !== null) {
        adminEnsureSubjectsSchema($db);
        $rows = $db->query('SELECT slug, label FROM custom_subjects ORDER BY label ASC')->fetchAll();

        foreach ($rows as $row) {
            $slug = strtolower(trim((string) ($row['slug'] ?? '')));
            $label = trim((string) ($row['label'] ?? ''));

            if ($slug !== '' && $label !== '') {
                $subjects[$slug] = $label;
            }
        }
    }

    return $subjects;
}

function adminNormalizeSubjectCategory($category)
{
    $value = strtolower(trim((string) $category));
    if (in_array($value, ['junior', 'senior', 'both'], true)) {
        return $value;
    }
    return 'both';
}

function adminNormalizeAssignedSubjectCategories($map, $allowedSubjects = null)
{
    if (!is_array($map)) {
        return [];
    }

    $allowedKeys = [];
    if (is_array($allowedSubjects)) {
        $allowedKeys = array_keys($allowedSubjects);
    }

    $normalized = [];
    foreach ($map as $subject => $category) {
        $key = adminSubjectSlug($subject);
        if ($key === '') {
            continue;
        }

        if (!empty($allowedKeys) && !in_array($key, $allowedKeys, true)) {
            continue;
        }

        $normalized[$key] = adminNormalizeSubjectCategory($category);
    }

    return $normalized;
}

function adminAssignedSubjectCategoriesToStorage($map, $allowedSubjects = null)
{
    $normalized = adminNormalizeAssignedSubjectCategories($map, $allowedSubjects);
    return empty($normalized) ? '{}' : json_encode($normalized);
}

function adminAssignedSubjectCategoriesFromStorage($stored, $allowedSubjects = null)
{
    if (is_array($stored)) {
        return adminNormalizeAssignedSubjectCategories($stored, $allowedSubjects);
    }

    $decoded = json_decode((string) $stored, true);
    if (!is_array($decoded)) {
        return [];
    }

    return adminNormalizeAssignedSubjectCategories($decoded, $allowedSubjects);
}

function adminBuildTeacherSubjectCategoryMap($juniorSubjectsInput, $seniorSubjectsInput, $allowedSubjects = null)
{
    // Combine junior/senior selections into one saved map.
    $junior = adminNormalizeSubjects($juniorSubjectsInput, $allowedSubjects);
    $senior = adminNormalizeSubjects($seniorSubjectsInput, $allowedSubjects);
    $allKeys = array_values(array_unique(array_merge($junior, $senior)));

    $map = [];
    foreach ($allKeys as $subjectKey) {
        $hasJunior = in_array($subjectKey, $junior, true);
        $hasSenior = in_array($subjectKey, $senior, true);

        if ($hasJunior && $hasSenior) {
            $map[$subjectKey] = 'both';
        } elseif ($hasJunior) {
            $map[$subjectKey] = 'junior';
        } elseif ($hasSenior) {
            $map[$subjectKey] = 'senior';
        }
    }

    return $map;
}

function adminCategorizedSubjects($db)
{
    // Build subject lists for junior, senior, and all.
    adminEnsureSubjectsSchema($db);

    $baseSubjects = [
        'english' => 'English',
        'mathematics' => 'Mathematics'
    ];

    $result = [
        'junior' => $baseSubjects,
        'senior' => $baseSubjects,
        'all' => $baseSubjects
    ];

    $rows = $db->query('SELECT slug, label, category FROM custom_subjects ORDER BY label ASC')->fetchAll();

    foreach ($rows as $row) {
        $slug = strtolower(trim((string) ($row['slug'] ?? '')));
        $label = trim((string) ($row['label'] ?? ''));
        $category = adminNormalizeSubjectCategory($row['category'] ?? 'both');

        if ($slug === '' || $label === '') {
            continue;
        }

        $result['all'][$slug] = $label;

        if ($category === 'junior' || $category === 'both') {
            $result['junior'][$slug] = $label;
        }

        if ($category === 'senior' || $category === 'both') {
            $result['senior'][$slug] = $label;
        }
    }

    return $result;
}

function adminSubjectCategories($db = null)
{
    $categories = [
        'english' => 'both',
        'mathematics' => 'both'
    ];

    if ($db !== null) {
        adminEnsureSubjectsSchema($db);
        $rows = $db->query('SELECT slug, category FROM custom_subjects')->fetchAll();

        foreach ($rows as $row) {
            $slug = strtolower(trim((string) ($row['slug'] ?? '')));
            if ($slug === '') {
                continue;
            }

            $categories[$slug] = adminNormalizeSubjectCategory($row['category'] ?? 'both');
        }
    }

    return $categories;
}

function adminNormalizeSubjects($subjectsInput, $allowedSubjects = null)
{
    // Clean subject values and keep only allowed ones.
    $normalized = [];

    if (is_string($subjectsInput)) {
        $subjectsInput = explode(',', $subjectsInput);
    }

    if (!is_array($subjectsInput)) {
        return [];
    }

    $allowedKeys = [];
    if (is_array($allowedSubjects)) {
        $allowedKeys = array_keys($allowedSubjects);
    }

    foreach ($subjectsInput as $subject) {
        $key = adminSubjectSlug($subject);
        if ($key === '') {
            continue;
        }

        if (empty($allowedKeys) || in_array($key, $allowedKeys, true)) {
            $normalized[$key] = $key;
        }
    }

    return array_values($normalized);
}

function adminSubjectsToStorage($subjects, $allowedSubjects = null)
{
    $normalized = adminNormalizeSubjects($subjects, $allowedSubjects);
    return empty($normalized) ? 'english' : implode(',', $normalized);
}

function adminSubjectsFromStorage($stored, $allowedSubjects = null)
{
    return adminNormalizeSubjects($stored, $allowedSubjects);
}

function adminSubjectsLabels($subjects, $availableSubjects = null)
{
    $available = is_array($availableSubjects) ? $availableSubjects : adminAvailableSubjects();
    $normalized = adminNormalizeSubjects($subjects);
    $labels = [];

    foreach ($normalized as $key) {
        $labels[] = $available[$key] ?? ucwords(str_replace('_', ' ', $key));
    }

    return $labels;
}

function adminSubjectSlug($label)
{
    $value = strtolower(trim((string) $label));
    $value = preg_replace('/[^a-z0-9]+/', '_', $value);
    $value = trim((string) $value, '_');
    return substr($value, 0, 80);
}

function adminSubjectStorageKey($label, $category = 'both')
{
    $base = adminSubjectSlug($label);
    if ($base === '') {
        return '';
    }

    $normalizedCategory = adminNormalizeSubjectCategory($category);
    if ($normalizedCategory === 'both') {
        return $base;
    }

    $suffix = '_cat_' . $normalizedCategory;
    $maxBaseLength = 80 - strlen($suffix);
    if ($maxBaseLength < 1) {
        return substr($base, 0, 80);
    }

    return substr($base, 0, $maxBaseLength) . $suffix;
}

function adminNotificationTypeOptions()
{
    return [
        'important' => 'Important',
        'meeting' => 'Meeting',
        'general' => 'General'
    ];
}

function adminNormalizeNotificationType($type)
{
    $value = strtolower(trim((string) $type));
    $allowed = adminNotificationTypeOptions();
    return array_key_exists($value, $allowed) ? $value : 'general';
}

function adminEnsureNotificationsSchema($db)
{
    $db->query(
        'CREATE TABLE IF NOT EXISTS admin_notifications (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(160) NOT NULL,
            message TEXT NOT NULL,
            type VARCHAR(20) NOT NULL DEFAULT "general",
            is_deleted TINYINT(1) NOT NULL DEFAULT 0,
            deleted_at DATETIME NULL,
            edited_at DATETIME NULL,
            created_by INT(11) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );

    $columns = $db->query('DESCRIBE admin_notifications')->fetchAll();
    $hasDeletedFlag = false;
    $hasDeletedAt = false;
    $hasEditedAt = false;

    foreach ($columns as $column) {
        $field = strtolower((string) ($column['Field'] ?? ''));
        if ($field === 'is_deleted') {
            $hasDeletedFlag = true;
        } elseif ($field === 'deleted_at') {
            $hasDeletedAt = true;
        } elseif ($field === 'edited_at') {
            $hasEditedAt = true;
        }
    }

    if (!$hasDeletedFlag) {
        $db->query('ALTER TABLE admin_notifications ADD COLUMN is_deleted TINYINT(1) NOT NULL DEFAULT 0 AFTER type');
    }

    if (!$hasDeletedAt) {
        $db->query('ALTER TABLE admin_notifications ADD COLUMN deleted_at DATETIME NULL AFTER is_deleted');
    }

    if (!$hasEditedAt) {
        $db->query('ALTER TABLE admin_notifications ADD COLUMN edited_at DATETIME NULL AFTER deleted_at');
    }
}

function adminPurgeExpiredNotifications($db)
{
    // Keep notifications for 6 days, then clear automatically.
    $db->query('DELETE FROM admin_notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 6 DAY)');
}

function adminCreateNotification($db, $title, $message, $type = 'general', $createdBy = null)
{
    $safeType = adminNormalizeNotificationType($type);
    $creatorId = (int) $createdBy;
    if ($creatorId <= 0) {
        $creatorId = null;
    }

    $db->query(
        'INSERT INTO admin_notifications (title, message, type, created_by)
         VALUES (:title, :message, :type, :created_by)',
        [
            'title' => trim((string) $title),
            'message' => trim((string) $message),
            'type' => $safeType,
            'created_by' => $creatorId
        ]
    );
}

function adminUpdateNotificationById($db, $id, $title, $message, $type = 'general')
{
    $notificationId = (int) $id;
    if ($notificationId <= 0) {
        return;
    }

    $safeType = adminNormalizeNotificationType($type);
    $db->query(
        'UPDATE admin_notifications
         SET title = :title,
             message = :message,
             type = :type,
             edited_at = NOW()
         WHERE id = :id
         LIMIT 1',
        [
            'id' => $notificationId,
            'title' => trim((string) $title),
            'message' => trim((string) $message),
            'type' => $safeType
        ]
    );
}

function adminFetchLatestNotifications($db, $limit = 80, $includeDeleted = false)
{
    adminPurgeExpiredNotifications($db);
    $rowLimit = max(1, min(200, (int) $limit));
    $whereClause = $includeDeleted ? '' : 'WHERE is_deleted = 0';
    return $db->query(
        "SELECT id, title, message, type, is_deleted, edited_at, created_by, created_at
         FROM admin_notifications
         {$whereClause}
         ORDER BY created_at DESC, id DESC
         LIMIT {$rowLimit}"
    )->fetchAll();
}

function adminDeleteNotificationById($db, $id)
{
    $notificationId = (int) $id;
    if ($notificationId <= 0) {
        return;
    }

    // Soft-delete so teachers can see that the message was removed.
    $db->query(
        'UPDATE admin_notifications
         SET is_deleted = 1,
             deleted_at = NOW()
         WHERE id = :id
         LIMIT 1',
        [
        'id' => $notificationId
        ]
    );
}

function adminGroupNotificationsByDate($rows)
{
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $grouped = [];

    foreach (($rows ?? []) as $row) {
        $createdAt = (string) ($row['created_at'] ?? '');
        if ($createdAt === '') {
            continue;
        }

        $recordDate = date('Y-m-d', strtotime($createdAt));
        if ($recordDate === $today) {
            $sectionTitle = 'Today';
        } elseif ($recordDate === $yesterday) {
            $sectionTitle = 'Yesterday';
        } else {
            $sectionTitle = date('l, F j, Y', strtotime($createdAt));
        }

        if (!isset($grouped[$sectionTitle])) {
            $grouped[$sectionTitle] = [];
        }

        $grouped[$sectionTitle][] = $row;
    }

    return $grouped;
}

function adminEnsureTeacherAlertsSchema($db)
{
    $db->query(
        'CREATE TABLE IF NOT EXISTS teacher_admin_alerts (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            teacher_user_id INT(11) NOT NULL,
            title VARCHAR(160) NOT NULL,
            message TEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT "open",
            resolved_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );

    $columns = $db->query('DESCRIBE teacher_admin_alerts')->fetchAll();
    $hasStatus = false;
    $hasResolvedAt = false;
    $hasOriginRole = false;
    $hasCreatedByAdmin = false;
    $hasTeacherReadAt = false;

    foreach ($columns as $column) {
        $field = strtolower((string) ($column['Field'] ?? ''));
        if ($field === 'status') {
            $hasStatus = true;
        } elseif ($field === 'resolved_at') {
            $hasResolvedAt = true;
        } elseif ($field === 'origin_role') {
            $hasOriginRole = true;
        } elseif ($field === 'created_by_admin_user_id') {
            $hasCreatedByAdmin = true;
        } elseif ($field === 'teacher_read_at') {
            $hasTeacherReadAt = true;
        }
    }

    if (!$hasStatus) {
        $db->query('ALTER TABLE teacher_admin_alerts ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT "open" AFTER message');
    }

    if (!$hasResolvedAt) {
        $db->query('ALTER TABLE teacher_admin_alerts ADD COLUMN resolved_at DATETIME NULL AFTER status');
    }

    if (!$hasOriginRole) {
        $db->query('ALTER TABLE teacher_admin_alerts ADD COLUMN origin_role VARCHAR(20) NOT NULL DEFAULT "teacher" AFTER message');
    }

    if (!$hasCreatedByAdmin) {
        $db->query('ALTER TABLE teacher_admin_alerts ADD COLUMN created_by_admin_user_id INT(11) NULL AFTER teacher_user_id');
    }

    if (!$hasTeacherReadAt) {
        $db->query('ALTER TABLE teacher_admin_alerts ADD COLUMN teacher_read_at DATETIME NULL AFTER status');
    }

    $db->query("UPDATE teacher_admin_alerts SET status = 'open' WHERE status IS NULL OR TRIM(status) = ''");
    $db->query("UPDATE teacher_admin_alerts SET origin_role = 'teacher' WHERE origin_role IS NULL OR TRIM(origin_role) = ''");
    $db->query('UPDATE teacher_admin_alerts SET teacher_read_at = created_at WHERE origin_role = "teacher" AND teacher_read_at IS NULL');
    adminEnsureTeacherAlertRepliesSchema($db);
}

function adminNormalizeTeacherAlertStatus($status)
{
    $value = strtolower(trim((string) $status));
    return in_array($value, ['open', 'resolved'], true) ? $value : 'open';
}

function adminMessageTitleFromBody($title, $message)
{
    $rawTitle = trim((string) $title);
    if ($rawTitle !== '') {
        return substr($rawTitle, 0, 160);
    }

    $plain = trim(preg_replace('/\s+/', ' ', strip_tags((string) $message)));
    if ($plain === '') {
        return 'Message';
    }

    return substr($plain, 0, 60);
}

function adminCreateTeacherAlert($db, $teacherUserId, $title, $message)
{
    $userId = (int) $teacherUserId;
    if ($userId <= 0) {
        return;
    }

    $safeMessage = trim((string) $message);
    $safeTitle = adminMessageTitleFromBody($title, $safeMessage);

    $db->query(
        'INSERT INTO teacher_admin_alerts (teacher_user_id, created_by_admin_user_id, title, message, origin_role, status, teacher_read_at)
         VALUES (:teacher_user_id, NULL, :title, :message, "teacher", "open", NOW())',
        [
            'teacher_user_id' => $userId,
            'title' => $safeTitle,
            'message' => $safeMessage
        ]
    );
}

function adminCreateAdminTeacherMessage($db, $teacherUserId, $adminUserId, $title, $message)
{
    $safeTeacherId = (int) $teacherUserId;
    $safeAdminId = (int) $adminUserId;
    if ($safeTeacherId <= 0) {
        return;
    }

    $safeMessage = trim((string) $message);
    $safeTitle = adminMessageTitleFromBody($title, $safeMessage);

    $db->query(
        'INSERT INTO teacher_admin_alerts (teacher_user_id, created_by_admin_user_id, title, message, origin_role, status, teacher_read_at)
         VALUES (:teacher_user_id, :created_by_admin_user_id, :title, :message, "admin", "open", NULL)',
        [
            'teacher_user_id' => $safeTeacherId,
            'created_by_admin_user_id' => $safeAdminId > 0 ? $safeAdminId : null,
            'title' => $safeTitle,
            'message' => $safeMessage
        ]
    );
}

function adminFetchTeacherAlerts($db, $limit = 120, $status = null, $teacherUserId = null)
{
    adminEnsureTeacherAlertsSchema($db);

    $rowLimit = max(1, min(200, (int) $limit));
    $where = [];
    $params = [];

    if ($status !== null) {
        $where[] = 'a.status = :status';
        $params['status'] = adminNormalizeTeacherAlertStatus($status);
    }

    $teacherId = (int) $teacherUserId;
    if ($teacherId > 0) {
        $where[] = 'a.teacher_user_id = :teacher_user_id';
        $params['teacher_user_id'] = $teacherId;
    }

    $whereClause = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));

    return $db->query(
        "SELECT a.id, a.teacher_user_id, a.created_by_admin_user_id, a.title, a.message, a.origin_role, a.status, a.teacher_read_at, a.resolved_at, a.created_at, u.name AS teacher_name, admin_sender.name AS admin_sender_name
         FROM teacher_admin_alerts a
         LEFT JOIN teacher_users u ON u.id = a.teacher_user_id
         LEFT JOIN teacher_users admin_sender ON admin_sender.id = a.created_by_admin_user_id
         {$whereClause}
         ORDER BY a.created_at DESC, a.id DESC
         LIMIT {$rowLimit}",
        $params
    )->fetchAll();
}

function adminResolveTeacherAlertById($db, $id)
{
    $alertId = (int) $id;
    if ($alertId <= 0) {
        return;
    }

    $db->query(
        'UPDATE teacher_admin_alerts
         SET status = "resolved",
             resolved_at = NOW()
         WHERE id = :id
         LIMIT 1',
        ['id' => $alertId]
    );
}

function adminEnsureTeacherAlertRepliesSchema($db)
{
    $db->query(
        'CREATE TABLE IF NOT EXISTS teacher_admin_alert_replies (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            alert_id INT(11) NOT NULL,
            teacher_user_id INT(11) NOT NULL,
            admin_user_id INT(11) NULL,
            message TEXT NOT NULL,
            teacher_read_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );

    $columns = $db->query('DESCRIBE teacher_admin_alert_replies')->fetchAll();
    $hasTeacherReadAt = false;

    foreach ($columns as $column) {
        $field = strtolower((string) ($column['Field'] ?? ''));
        if ($field === 'teacher_read_at') {
            $hasTeacherReadAt = true;
        }
    }

    if (!$hasTeacherReadAt) {
        $db->query('ALTER TABLE teacher_admin_alert_replies ADD COLUMN teacher_read_at DATETIME NULL AFTER message');
    }
}

function adminCreateTeacherAlertReply($db, $alertId, $teacherUserId, $adminUserId, $message)
{
    $safeAlertId = (int) $alertId;
    $safeTeacherId = (int) $teacherUserId;
    $safeAdminId = (int) $adminUserId;
    $safeMessage = trim((string) $message);

    if ($safeAlertId <= 0 || $safeTeacherId <= 0 || $safeMessage === '') {
        return;
    }

    adminEnsureTeacherAlertRepliesSchema($db);

    $db->query(
        'INSERT INTO teacher_admin_alert_replies (alert_id, teacher_user_id, admin_user_id, message, teacher_read_at)
         VALUES (:alert_id, :teacher_user_id, :admin_user_id, :message, NULL)',
        [
            'alert_id' => $safeAlertId,
            'teacher_user_id' => $safeTeacherId,
            'admin_user_id' => $safeAdminId > 0 ? $safeAdminId : null,
            'message' => $safeMessage
        ]
    );
}

function adminFetchTeacherAlertReplies($db, $limit = 300, $teacherUserId = null, $alertId = null)
{
    adminEnsureTeacherAlertRepliesSchema($db);

    $rowLimit = max(1, min(1000, (int) $limit));
    $where = [];
    $params = [];

    $safeTeacherId = (int) $teacherUserId;
    if ($safeTeacherId > 0) {
        $where[] = 'r.teacher_user_id = :teacher_user_id';
        $params['teacher_user_id'] = $safeTeacherId;
    }

    $safeAlertId = (int) $alertId;
    if ($safeAlertId > 0) {
        $where[] = 'r.alert_id = :alert_id';
        $params['alert_id'] = $safeAlertId;
    }

    $whereClause = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));

    return $db->query(
        "SELECT r.id, r.alert_id, r.teacher_user_id, r.admin_user_id, r.message, r.teacher_read_at, r.created_at, u.name AS admin_name
         FROM teacher_admin_alert_replies r
         LEFT JOIN teacher_users u ON u.id = r.admin_user_id
         {$whereClause}
         ORDER BY r.created_at ASC, r.id ASC
         LIMIT {$rowLimit}",
        $params
    )->fetchAll();
}

function adminRepliesByAlert($rows)
{
    $grouped = [];
    foreach (($rows ?? []) as $row) {
        $alertId = (int) ($row['alert_id'] ?? 0);
        if ($alertId <= 0) {
            continue;
        }

        if (!isset($grouped[$alertId])) {
            $grouped[$alertId] = [];
        }
        $grouped[$alertId][] = $row;
    }

    return $grouped;
}

function adminMarkTeacherRepliesRead($db, $teacherUserId)
{
    $safeTeacherId = (int) $teacherUserId;
    if ($safeTeacherId <= 0) {
        return;
    }

    adminEnsureTeacherAlertRepliesSchema($db);

    $db->query(
        'UPDATE teacher_admin_alert_replies
         SET teacher_read_at = NOW()
         WHERE teacher_user_id = :teacher_user_id
           AND teacher_read_at IS NULL',
        ['teacher_user_id' => $safeTeacherId]
    );
}

function adminCountUnreadTeacherReplies($db, $teacherUserId)
{
    $safeTeacherId = (int) $teacherUserId;
    if ($safeTeacherId <= 0) {
        return 0;
    }

    adminEnsureTeacherAlertRepliesSchema($db);

    $row = $db->query(
        'SELECT COUNT(*) AS total
         FROM teacher_admin_alert_replies
         WHERE teacher_user_id = :teacher_user_id
           AND teacher_read_at IS NULL',
        ['teacher_user_id' => $safeTeacherId]
    )->fetch();

    return (int) ($row['total'] ?? 0);
}

function adminMarkTeacherAlertMessagesRead($db, $teacherUserId)
{
    $safeTeacherId = (int) $teacherUserId;
    if ($safeTeacherId <= 0) {
        return;
    }

    $db->query(
        'UPDATE teacher_admin_alerts
         SET teacher_read_at = NOW()
         WHERE teacher_user_id = :teacher_user_id
           AND origin_role = "admin"
           AND teacher_read_at IS NULL',
        ['teacher_user_id' => $safeTeacherId]
    );
}

function adminCountUnreadTeacherInbox($db, $teacherUserId)
{
    $safeTeacherId = (int) $teacherUserId;
    if ($safeTeacherId <= 0) {
        return 0;
    }

    $row = $db->query(
        'SELECT COUNT(*) AS total
         FROM teacher_admin_alerts
         WHERE teacher_user_id = :teacher_user_id
           AND origin_role = "admin"
           AND teacher_read_at IS NULL',
        ['teacher_user_id' => $safeTeacherId]
    )->fetch();

    $unreadDirect = (int) ($row['total'] ?? 0);
    $unreadReplies = adminCountUnreadTeacherReplies($db, $safeTeacherId);

    return $unreadDirect + $unreadReplies;
}

function adminEnsureAuditLogsSchema($db)
{
    $db->query(
        'CREATE TABLE IF NOT EXISTS admin_audit_logs (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            admin_user_id INT(11) NULL,
            action_key VARCHAR(80) NOT NULL,
            entity_type VARCHAR(60) NOT NULL,
            entity_id VARCHAR(120) NULL,
            summary VARCHAR(255) NOT NULL,
            context_json TEXT NULL,
            ip_address VARCHAR(64) NULL,
            user_agent VARCHAR(255) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );
}

function adminClientIp()
{
    $candidates = [
        (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''),
        (string) ($_SERVER['REMOTE_ADDR'] ?? '')
    ];

    foreach ($candidates as $candidate) {
        $parts = array_filter(array_map('trim', explode(',', $candidate)));
        if (!empty($parts)) {
            return (string) $parts[0];
        }
    }

    return null;
}

function adminAuditLog($db, $actionKey, $entityType, $entityId = null, $summary = '', $context = [])
{
    adminEnsureAuditLogsSchema($db);
    $adminUserId = (int) (Session::get('user')['id'] ?? 0);
    if ($adminUserId <= 0) {
        $adminUserId = null;
    }

    $db->query(
        'INSERT INTO admin_audit_logs (admin_user_id, action_key, entity_type, entity_id, summary, context_json, ip_address, user_agent, created_at)
         VALUES (:admin_user_id, :action_key, :entity_type, :entity_id, :summary, :context_json, :ip_address, :user_agent, NOW())',
        [
            'admin_user_id' => $adminUserId,
            'action_key' => trim((string) $actionKey),
            'entity_type' => trim((string) $entityType),
            'entity_id' => $entityId === null ? null : (string) $entityId,
            'summary' => trim((string) $summary),
            'context_json' => empty($context) ? null : json_encode($context),
            'ip_address' => adminClientIp(),
            'user_agent' => substr(trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 255)
        ]
    );
}
