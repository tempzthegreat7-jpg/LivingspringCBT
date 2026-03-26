<?php


class Authorize
{
    /**
     * Check if user is authenticated
     * 
     * @return bool
     */

    public function isAuth()
    {
        return Session::has('user');
    }



    /**
     * Handle the user's request
     * 
     * @param string $role
     * @return bool
     */

    public function handle($role)
    {
        if ($role === 'guest' && $this->isAuth()) {
            echo '/roles';
        } elseif ($role === 'teacher' && !$this->isAuth()) {
            echo '/teacher/login';
        } elseif ($role === 'student' && !$this->isAuth()) {
            echo '/student/names';
        }
    }
}
