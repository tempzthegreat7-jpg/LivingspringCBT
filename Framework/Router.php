<?php

require basePath('Framework/middleware/Authorize.php');

class Router
{
    protected $routes = [];

    public function add($method, $path, $controller)
    {
        list($controller, $controllerFile) = explode('@', $controller);

        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'controller' => $controller,
            'controllerFile' => $controllerFile,
            'middleware' => null
        ];

        return $this;
    }

    /**
     * Add a Get Route
     * 
     * @param mixed $uri
     * @param mixed $controller
     */

    public function get($uri, $controller)
    {
        return $this->add('GET', $uri, $controller);
    }

    /**
     * Add a Post Route
     * 
     * @param mixed $uri
     * @param mixed $controller
     */

    public function post($uri, $controller)
    {
        return $this->add('POST', $uri, $controller);
    }


    public function only($key)
    {
        $this->routes[array_key_last($this->routes)]['middleware'] = $key;

        return $this;
    }

    /**
     * Routes the URL
     * 
     * @param mixed $url
     * 
     */

    public function dispatch($url, $method)
    {
        foreach ($this->routes as $route) {
            if ($route['method'] === strtoupper($method) && $url == $route['path']) {
                if (strtoupper((string) $method) === 'POST') {
                    $csrfToken = $_POST['_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
                    $isValidCsrf = Session::verifyCsrfToken($csrfToken);

                    if (!$isValidCsrf) {
                        http_response_code(419);
                        $acceptHeader = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
                        $isJsonExpected = strpos($acceptHeader, 'application/json') !== false
                            || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

                        if ($isJsonExpected) {
                            header('Content-Type: application/json');
                            echo json_encode([
                                'ok' => false,
                                'message' => 'Invalid request token. Please refresh and try again.'
                            ]);
                            return;
                        }

                        Session::setFlashMesssge('error_message', 'Request expired. Please try again.');
                        redirect($_SERVER['HTTP_REFERER'] ?? '/roles');
                    }
                }

                $sessionUser = Session::get('user');
                $isLoggedIn = Session::has('user');
                $userRole = strtolower($sessionUser['role'] ?? 'teacher');
                $canSetQuestions = (int) ($sessionUser['can_set_questions'] ?? 1) === 1;
                $isActiveUser = (int) ($sessionUser['is_active'] ?? 1) === 1;
                $dbCheckedUser = null;
                $didCheckDbUser = false;

                $syncSessionUserFromDb = function () use (&$sessionUser, &$isLoggedIn, &$userRole, &$canSetQuestions, &$isActiveUser, &$dbCheckedUser, &$didCheckDbUser) {
                    if ($didCheckDbUser) {
                        return $dbCheckedUser;
                    }

                    $didCheckDbUser = true;
                    if (!$isLoggedIn) {
                        return null;
                    }

                    $userId = (int) ($sessionUser['id'] ?? 0);
                    if ($userId <= 0) {
                        Session::clear('user');
                        $isLoggedIn = false;
                        return null;
                    }

                    $config = require basePath('config/config-db.php');
                    try {
                        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbName']}";
                        $pdo = new PDO($dsn, $config['username'], $config['password'], [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                        ]);

                        $stmt = $pdo->prepare(
                            'SELECT id, name, role, can_set_questions, is_active, can_manage_students, assigned_subjects, assigned_subject_categories
                             FROM teacher_users
                             WHERE id = :id
                             LIMIT 1'
                        );
                        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
                        $stmt->execute();
                        $dbCheckedUser = $stmt->fetch();
                    } catch (Exception $e) {
                        Session::clear('user');
                        $isLoggedIn = false;
                        return null;
                    }

                    if (!$dbCheckedUser) {
                        Session::clear('user');
                        $isLoggedIn = false;
                        return null;
                    }

                    $sessionUser = [
                        'id' => (int) ($dbCheckedUser['id'] ?? 0),
                        'name' => (string) ($dbCheckedUser['name'] ?? ''),
                        'role' => strtolower((string) ($dbCheckedUser['role'] ?? 'teacher')) === 'admin' ? 'admin' : 'teacher',
                        'can_set_questions' => (int) ($dbCheckedUser['can_set_questions'] ?? 1),
                        'is_active' => (int) ($dbCheckedUser['is_active'] ?? 1),
                        'can_manage_students' => (int) ($dbCheckedUser['can_manage_students'] ?? 0),
                        'assigned_subjects' => (string) ($dbCheckedUser['assigned_subjects'] ?? 'english'),
                        'assigned_subject_categories' => (string) ($dbCheckedUser['assigned_subject_categories'] ?? '{}')
                    ];

                    Session::set('user', $sessionUser);
                    $userRole = strtolower((string) $sessionUser['role']);
                    $canSetQuestions = (int) ($sessionUser['can_set_questions'] ?? 1) === 1;
                    $isActiveUser = (int) ($sessionUser['is_active'] ?? 1) === 1;
                    $isLoggedIn = true;

                    return $dbCheckedUser;
                };

                $maintenanceMode = [
                    'enabled' => false,
                    'message' => 'We are updating the platform. Please check back shortly.'
                ];
                try {
                    require_once basePath('App/controllers/AdminController/shared.php');
                    $maintenanceConfig = require basePath('config/config-db.php');
                    $maintenancePdo = new PDO(
                        "mysql:host={$maintenanceConfig['host']};port={$maintenanceConfig['port']};dbname={$maintenanceConfig['dbName']}",
                        $maintenanceConfig['username'],
                        $maintenanceConfig['password'],
                        [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                        ]
                    );
                    $maintenancePdo->exec(
                        'CREATE TABLE IF NOT EXISTS app_maintenance_mode (
                            id TINYINT(1) NOT NULL PRIMARY KEY,
                            is_enabled TINYINT(1) NOT NULL DEFAULT 0,
                            message VARCHAR(255) NOT NULL DEFAULT "We are updating the platform. Please check back shortly.",
                            updated_by_admin_id INT(11) NULL,
                            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                        )'
                    );
                    $maintenancePdo->prepare(
                        'INSERT INTO app_maintenance_mode (id, is_enabled, message, updated_by_admin_id)
                         VALUES (1, 0, :message, NULL)
                         ON DUPLICATE KEY UPDATE id = id'
                    )->execute([
                        ':message' => 'We are updating the platform. Please check back shortly.'
                    ]);
                    $maintenanceRow = $maintenancePdo->query(
                        'SELECT is_enabled, message
                         FROM app_maintenance_mode
                         WHERE id = 1
                         LIMIT 1'
                    )->fetch();
                    $maintenanceMode = [
                        'enabled' => (int) ($maintenanceRow['is_enabled'] ?? 0) === 1,
                        'message' => trim((string) ($maintenanceRow['message'] ?? 'We are updating the platform. Please check back shortly.'))
                    ];
                } catch (Exception $e) {
                    $maintenanceMode = [
                        'enabled' => false,
                        'message' => 'We are updating the platform. Please check back shortly.'
                    ];
                }

                $maintenanceAllowedPaths = ['/admin/login'];
                $maintenanceBypass = in_array((string) ($route['middleware'] ?? ''), ['admin', 'admin_guest'], true)
                    || in_array((string) $route['path'], $maintenanceAllowedPaths, true);
                if (!empty($maintenanceMode['enabled']) && !$maintenanceBypass) {
                    $acceptHeader = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
                    $isJsonExpected = strpos($acceptHeader, 'application/json') !== false
                        || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

                    if ($isJsonExpected) {
                        http_response_code(503);
                        header('Content-Type: application/json');
                        echo json_encode([
                            'ok' => false,
                            'message' => (string) ($maintenanceMode['message'] ?? 'We are updating the platform. Please check back shortly.'),
                            'redirect' => '/roles'
                        ]);
                        return;
                    }

                    http_response_code(503);
                    loadView('maintenance', [
                        'message' => (string) ($maintenanceMode['message'] ?? 'We are updating the platform. Please check back shortly.')
                    ]);
                    return;
                }

                if ($route['middleware'] === 'guest') {
                    if ($isLoggedIn) {
                        $syncSessionUserFromDb();
                    }

                    if ($isLoggedIn) {
                        $teacherAuthPaths = ['/teacher/subject', '/teacher/login'];

                        if (in_array($route['path'], $teacherAuthPaths, true)) {
                            // Allow signed-in users to switch context via teacher login flow.
                        } elseif ($userRole === 'admin') {
                            redirect('/admin/dashboard');
                        } else {
                            redirect('/teacher');
                        }
                    } elseif (Session::has('student') ?? '') {
                        redirect('/student/dashboard');
                    }
                } elseif ($route['middleware'] === 'student') {
                    if (!Session::has('student')) {
                        redirect('/student/names');
                    }

                    require_once basePath('App/controllers/AdminController/shared.php');

                    $studentSession = Session::get('student') ?? [];
                    $studentId = (int) ($studentSession['id'] ?? 0);
                    $studentToken = trim((string) ($studentSession['session_token'] ?? ''));
                    $acceptHeader = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
                    $isJsonExpected = strpos($acceptHeader, 'application/json') !== false
                        || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
                    $denyStudentAccess = function ($message) use ($isJsonExpected) {
                        Session::clear('student');
                        Session::clear('quiz');
                        Session::clear('subjects');

                        if ($isJsonExpected) {
                            http_response_code(401);
                            header('Content-Type: application/json');
                            echo json_encode([
                                'ok' => false,
                                'message' => (string) $message,
                                'redirect' => '/student/names'
                            ]);
                            return;
                        }

                        Session::setFlashMesssge('error_message', (string) $message);
                        redirect('/student/names');
                    };

                    if ($studentId <= 0 || $studentToken === '') {
                        $denyStudentAccess('Your student session has expired. Please log in again.');
                        return;
                    }

                    $config = require basePath('config/config-db.php');
                    try {
                        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbName']}";
                        $studentPdo = new PDO($dsn, $config['username'], $config['password'], [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                        ]);

                        $studentPdo->exec(
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

                        $studentPdo->exec(
                            'CREATE TABLE IF NOT EXISTS student_class_locks (
                                student_class VARCHAR(20) NOT NULL PRIMARY KEY,
                                is_locked TINYINT(1) NOT NULL DEFAULT 0,
                                updated_by_admin_id INT(11) NULL,
                                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                            )'
                        );

                        $studentColumns = $studentPdo->query('DESCRIBE student_users')->fetchAll();
                        $studentColumnMap = [];
                        foreach ($studentColumns as $studentColumn) {
                            $studentColumnMap[(string) ($studentColumn['Field'] ?? '')] = true;
                        }

                        if (!isset($studentColumnMap['active_session_token'])) {
                            $studentPdo->exec('ALTER TABLE student_users ADD COLUMN active_session_token VARCHAR(128) NULL AFTER is_active');
                        }
                        if (!isset($studentColumnMap['active_session_seen_at'])) {
                            $studentPdo->exec('ALTER TABLE student_users ADD COLUMN active_session_seen_at DATETIME NULL AFTER active_session_token');
                        }

                        $studentStmt = $studentPdo->prepare(
                            'SELECT id, student_name, student_class, is_active, active_session_token
                             FROM student_users
                             WHERE id = :id
                             LIMIT 1'
                        );
                        $studentStmt->bindValue(':id', $studentId, PDO::PARAM_INT);
                        $studentStmt->execute();
                        $studentRow = $studentStmt->fetch();

                        $studentClassLockStmt = $studentPdo->prepare(
                            'SELECT is_locked
                             FROM student_class_locks
                             WHERE student_class = :student_class
                             LIMIT 1'
                        );
                        $studentClassLockStmt->bindValue(':student_class', strtoupper((string) ($studentSession['class'] ?? 'SS3')), PDO::PARAM_STR);
                        $studentClassLockStmt->execute();
                        $studentClassLockRow = $studentClassLockStmt->fetch();
                    } catch (Exception $e) {
                        $denyStudentAccess('Unable to verify your student session right now. Please log in again.');
                        return;
                    }

                    if (!$studentRow) {
                        $denyStudentAccess('Student login not found. Please log in again.');
                        return;
                    }

                    if ((int) ($studentRow['is_active'] ?? 0) !== 1) {
                        $denyStudentAccess('This student login is inactive. Contact the admin.');
                        return;
                    }

                    if ((int) ($studentClassLockRow['is_locked'] ?? 0) === 1) {
                        $denyStudentAccess('This class is currently locked. Contact the admin.');
                        return;
                    }

                    $dbToken = trim((string) ($studentRow['active_session_token'] ?? ''));
                    if ($dbToken === '' || !hash_equals($dbToken, $studentToken)) {
                        $denyStudentAccess('Nice try. This account is already in use. Duplicate access is being watched.');
                        return;
                    }

                    try {
                        $studentSeenStmt = $studentPdo->prepare(
                            'UPDATE student_users
                             SET active_session_seen_at = NOW()
                             WHERE id = :id
                               AND active_session_token = :active_session_token
                             LIMIT 1'
                        );
                        $studentSeenStmt->bindValue(':id', $studentId, PDO::PARAM_INT);
                        $studentSeenStmt->bindValue(':active_session_token', $studentToken, PDO::PARAM_STR);
                        $studentSeenStmt->execute();
                    } catch (Exception $e) {
                        $denyStudentAccess('Unable to keep your student session active. Please log in again.');
                        return;
                    }
                } elseif ($route['middleware'] === 'teacher') {
                    if (!$isLoggedIn) {
                        redirect('/teacher/login');
                    }
                    $currentUser = $syncSessionUserFromDb();
                    if (!$currentUser) {
                        redirect('/teacher/login');
                    }
                    if (!$isActiveUser) {
                        Session::clearAll();
                        redirect('/teacher/login');
                    }
                    if (!in_array($userRole, ['teacher', 'admin'], true)) {
                        redirect('/roles');
                    }
                } elseif ($route['middleware'] === 'teacher_question') {
                    if (!$isLoggedIn) {
                        redirect('/teacher/login');
                    }
                    $currentUser = $syncSessionUserFromDb();
                    if (!$currentUser) {
                        redirect('/teacher/login');
                    }
                    if (!$isActiveUser) {
                        Session::clearAll();
                        redirect('/teacher/login');
                    }
                    if (!in_array($userRole, ['teacher', 'admin'], true)) {
                        redirect('/roles');
                    }
                    if (!$canSetQuestions) {
                        Session::setFlashMesssge('error_message', 'You are logged in but do not have question-management permission.');
                        redirect('/teacher');
                    }
                } elseif ($route['middleware'] === 'admin') {
                    if (!$isLoggedIn) {
                        redirect('/admin/login');
                    }
                    $currentUser = $syncSessionUserFromDb();
                    if (!$currentUser) {
                        redirect('/admin/login');
                    }
                    if (!$isActiveUser) {
                        Session::clearAll();
                        redirect('/admin/login');
                    }
                    if ($userRole !== 'admin') {
                        redirect('/teacher');
                    }
                } elseif ($route['middleware'] === 'admin_guest') {
                    if ($isLoggedIn) {
                        $syncSessionUserFromDb();
                    }

                    if ($isLoggedIn) {
                        if ($userRole === 'admin') {
                            redirect('/admin/dashboard');
                        }

                        redirect('/teacher');
                    }
                }

                $controller = basePath("App/controllers/{$route['controller']}/{$route['controllerFile']}.php");
                require $controller;
                return;
            }
        }
        http_response_code(404);
        $errorController = basePath('App/controllers/ErrorController/notfound.php');

        if (file_exists($errorController)) {
            require $errorController;
            return;
        }
    }
}
