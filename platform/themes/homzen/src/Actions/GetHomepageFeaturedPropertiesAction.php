<?php

namespace Theme\homzen\Actions;

use App\Actions\HomepageFeaturedPropertiesAction;

/**
 * @deprecated Use App\Actions\HomepageFeaturedPropertiesAction
 */
class GetHomepageFeaturedPropertiesAction
{
    public function handle(int $limit = 8): array
    {
        return app(HomepageFeaturedPropertiesAction::class)->handle($limit);
    }
}
