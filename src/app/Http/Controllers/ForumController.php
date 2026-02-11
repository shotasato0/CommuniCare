<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Services\ForumService;

class ForumController extends Controller
{
    private ForumService $forumService;

    public function __construct(ForumService $forumService)
    {
        $this->forumService = $forumService;
    }

    public function index(Request $request)
    {
        $forumData = $this->forumService->getForumData($request);
        
        return Inertia::render('Forum', $forumData);
    }
}
