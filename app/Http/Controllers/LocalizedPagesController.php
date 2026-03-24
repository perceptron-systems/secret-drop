<?php

namespace App\Http\Controllers;

use App\Support\LocaleConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LocalizedPagesController extends Controller
{
    public function show(string $locale, string $pageSlug): View|RedirectResponse
    {
        $page = LocaleConfig::findRouteBySlug($pageSlug, $locale);

        if ($page) {
            return view($page);
        }

        $page = LocaleConfig::findRouteBySlugAnyLocale($pageSlug);

        if ($page) {
            return redirect(localized_route($page, $locale), 301);
        }

        abort(404);
    }
}
