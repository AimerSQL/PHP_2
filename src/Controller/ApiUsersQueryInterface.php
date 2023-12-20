<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Interface ApiUsersQueryInterface
 *
 * @package App\Controller
 *
 */
interface ApiUsersQueryInterface
{
    public final const RUTA_API = '/api/v1/users';

    /**
     * **CGET** Action<br>
     * Summary: Retrieves the collection of User resources.<br>
     * _Notes_: Returns all users from the system that the user has access to.
     */
    public function cgetAction(Request $request): Response;

    /**
     * **GET** Action<br>
     * Summary: Retrieves a User resource based on a single ID.<br>
     * _Notes_: Returns the user identified by &#x60;_userId_&#x60;.
     *
     * @param int $userId User id
     */
    public function getAction(Request $request, int $userId): Response;

    /**
     * **OPTIONS** Action<br>
     * Summary: Provides the list of HTTP supported methods<br>
     * _Notes_: Return a &#x60;Allow&#x60; header with a list of HTTP supported methods.
     *
     * @param  int|null $userId User id
     */
    public function optionsAction(?int $userId): Response;
}
