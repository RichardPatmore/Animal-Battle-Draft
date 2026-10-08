<?php

use App\Http\Controllers\AnimalController;
use App\Http\Controllers\BrawlController;
use App\Http\Controllers\DraftController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/dashboard', fn () => redirect()->route('home'))->name('dashboard');

Route::post('/drafts', [DraftController::class, 'store'])->name('drafts.store');
Route::get('/drafts/{draft}', [DraftController::class, 'show'])->name('drafts.show');
Route::post('/drafts/{draft}/picks', [DraftController::class, 'pick'])->name('drafts.pick');
Route::patch('/drafts/{draft}/name', [DraftController::class, 'updateName'])->name('drafts.name');

Route::get('/drafts/{draft}/brawl', [BrawlController::class, 'index'])->name('brawl.index');
Route::get('/drafts/{draft}/brawl/summary', [BrawlController::class, 'summary'])->name('brawl.summary');
Route::post('/drafts/{draft}/brawl/start', [BrawlController::class, 'start'])->name('brawl.start');
Route::post('/drafts/{draft}/brawl/fight', [BrawlController::class, 'fight'])->name('brawl.fight');

Route::get('/animals', [AnimalController::class, 'index'])->name('animals.index');
Route::get('/animals/{animal}', [AnimalController::class, 'show'])->name('animals.show');

require __DIR__.'/settings.php';
