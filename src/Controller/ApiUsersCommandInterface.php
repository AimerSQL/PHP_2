<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ApiUsersController
 *
 * @package App\Controller
 *
 */
interface ApiUsersCommandInterface
{
    /**
     * **POST** action<br>
     * Summary: Creates a User resource.
     *
     * @param Request $request request
     */
    public function postAction(Request $request): Response;

    /**
     * **PUT** action<br>
     * Summary: Updates the User resource.<br>
     * _Notes_: Updates the user identified by &#x60;_userId_&#x60;.
     *
     * @param Request $request request
     * @param int $userId User id
     */
    public function putAction(Request $request, int $userId): Response;

    /**
     * **DELETE** Action<br>
     * Summary: Removes the User resource.<br>
     * _Notes_: Deletes the user identified by &#x60;userId&#x60;.
     *
     * @param int $userId User id
     */
    public function deleteAction(Request $request, int $userId): Response;
}
