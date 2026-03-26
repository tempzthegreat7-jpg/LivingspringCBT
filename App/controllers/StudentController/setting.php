<?php
// Subject/task selection page before starting quiz.
if ((int) ($_GET['fresh'] ?? 0) === 1) {
    Session::clear('quiz');
    Session::clear('subjects');
}

loadView('modes');
