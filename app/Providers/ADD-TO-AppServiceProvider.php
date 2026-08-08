<?php
// This is NOT a full file to copy over — it's just the two lines to ADD to
// your existing app/Providers/AppServiceProvider.php, so you don't wipe out
// anything else you've already put there.

// 1. Add this import near the top of the file, with the other `use` lines:
use App\Models\Review;
use App\Observers\ReviewObserver;

// 2. Inside the existing `public function boot(): void { ... }` method, add:
Review::observe(ReviewObserver::class);

/*
Example of what the whole method should look like afterwards:

public function boot(): void
{
    Review::observe(ReviewObserver::class);
}
*/
