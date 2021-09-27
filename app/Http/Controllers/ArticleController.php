<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

class ArticleController extends Controller
{
    public function index()
    {
        $limiter = app(RateLimiter::class);
        $actionKey = 'search_service';
        $threshold = 10;

        try {
            if ($limiter->tooManyAttempts($actionKey, $threshold)) {
                return $this->failOrFallback();
            }
            $response = Http::timeout(2)->get('https://fake-search-service.com/api/v1/search?q=Laravel');
            return view('test.index', ['articles' => $response->body()]);
        } catch (Exception $e) {
            $limiter->hit($actionKey, Carbon::now()->addMinutes(15));

            return $this->failOrFallback();
        }
    }
}
