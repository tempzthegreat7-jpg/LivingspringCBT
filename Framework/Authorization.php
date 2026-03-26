<?php

require basePath('Framework/Session.php');

class Authorization
{
    /**
     * Check to see if current logged in user owns a resource
     * 
     * @param int $resourceId
     * @return bool
     */

    public static function isOwner($resourceId)
    {
        $sessionUser = Session::get('user');

        if ($sessionUser !== null && isset($sessionUser['id'])) {
            $sessionUserId = (int) $sessionUser['id'];
            return $sessionUserId == $resourceId;
        }

        return false;
    }
}
