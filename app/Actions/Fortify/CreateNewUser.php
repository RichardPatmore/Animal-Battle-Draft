<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Public registration is disabled; scores use arcade display names only.
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
class CreateNewUser implements CreatesNewUsers
{
    /**
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        throw new NotFoundHttpException('Registration is disabled.');
    }
}
