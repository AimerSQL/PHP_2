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
interface ApiResultsQueryInterface
{
    public final const RUTA_API = '/api/v1/results';

    /**
     * **CGET** Action<br>
     * Summary: Retrieves the collection of Result resources.<br>
     * _Notes_: Returns all results from the system that the user has access to.
     */
    public function cgetAction(Request $request): Response;

    /**
     * **GET** Action<br>
     * Summary: Retrieves a Result resource based on a single ID.<br>
     * _Notes_: Returns the result identified by &#x60;_resultId_&#x60;.
     *
     * @param int $resultId Result id
     */
    public function getAction(Request $request, int $resultId): Response;

    /**
     * **OPTIONS** Action<br>
     * Summary: Provides the list of HTTP supported methods<br>
     * _Notes_: Return a &#x60;Allow&#x60; header with a list of HTTP supported methods.
     *
     * @param  int|null $resultId Result id
     */
    public function optionsAction(?int $resultId): Response;

    /**
     * **POST** action<br>
     * Summary: Creates a Result resource.
     *
     * @param Request $request request
     */
    public function postAction(Request $request): Response;

    /**
     * **PUT** action<br>
     * Summary: Updates the Result resource.<br>
     * _Notes_: Updates the result identified by &#x60;_resultId_&#x60;.
     *
     * @param Request $request request
     * @param int $resultId Result id
     */
    public function putAction(Request $request, int $resultId): Response;

    /**
     * **DELETE** Action<br>
     * Summary: Removes the Result resource.<br>
     * _Notes_: Deletes the result identified by &#x60;resultId&#x60;.
     *
     * @param int $resultId Result id
     */
    public function deleteAction(Request $request, int $resultId): Response;
}
