<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    /**
     * Renders the shell for admin/role/customer management. All data is
     * loaded client-side from the /api/v1/admin endpoints, which enforce
     * their own authorization independently of this page route.
     */
    public function index(): Response
    {
        return Inertia::render('admin/users/Index');
    }
}
