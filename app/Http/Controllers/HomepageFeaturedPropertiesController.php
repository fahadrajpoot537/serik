<?php

namespace App\Http\Controllers;

use App\Actions\HomepageFeaturedPropertiesAction;
use App\Support\VisitorLocationResolver;
use Botble\Theme\Facades\Theme;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class HomepageFeaturedPropertiesController extends Controller
{
    public function __invoke(Request $request, HomepageFeaturedPropertiesAction $action): Response
    {
        $location = VisitorLocationResolver::resolve($request, true);
        $result = $action->handleForLocation(8, $location);

        $html = Theme::partial('shortcodes.properties.styles.style-5', array_merge($result, [
            'featuredHydrate' => true,
        ])) ?: '';

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
