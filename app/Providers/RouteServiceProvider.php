protected function mapPlaceRoutes()
{
    Route::middleware('api') // or 'web' if it’s for web routes
         ->group(base_path('routes/PlaceRoutes.php'));
}
