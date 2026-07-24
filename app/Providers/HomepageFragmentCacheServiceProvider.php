<?php

namespace App\Providers;

use App\Support\HomepageFragmentCache;
use App\Support\HomepageResponseCache;
use Illuminate\Support\ServiceProvider;

final class HomepageFragmentCacheServiceProvider extends ServiceProvider
{
  public function boot(): void
  {
    add_filter('shortcode_get_callback', static function (callable $callback, string $name): callable {
      if (! HomepageFragmentCache::shouldCacheShortcode($name)) {
        return $callback;
      }

      return static function (...$args) use ($callback, $name): string {
        $compiled = $args[0] ?? null;

        if (! $compiled instanceof \Botble\Shortcode\Compilers\Shortcode) {
          return HomepageFragmentCache::stringifyOutput($callback(...$args));
        }

        return HomepageFragmentCache::rememberShortcode(
          $name,
          $compiled,
          static fn () => $callback(...$args)
        );
      };
    }, 20, 2);

    if (class_exists(\Botble\Menu\Models\Menu::class)) {
      $bustMenu = static function (): void {
        HomepageFragmentCache::bump('header_menu');
        HomepageResponseCache::bump();
      };

      \Botble\Menu\Models\Menu::saved($bustMenu);
      \Botble\Menu\Models\Menu::deleted($bustMenu);
    }

    if (class_exists(\Botble\Menu\Models\MenuNode::class)) {
      $bustMenu = static function (): void {
        HomepageFragmentCache::bump('header_menu');
        HomepageResponseCache::bump();
      };

      \Botble\Menu\Models\MenuNode::saved($bustMenu);
      \Botble\Menu\Models\MenuNode::deleted($bustMenu);
    }

    if (class_exists(\Botble\Testimonial\Models\Testimonial::class)) {
      $bustTestimonials = static function (): void {
        HomepageFragmentCache::bump('shortcode:testimonials');
        HomepageResponseCache::bump();
      };

      \Botble\Testimonial\Models\Testimonial::saved($bustTestimonials);
      \Botble\Testimonial\Models\Testimonial::deleted($bustTestimonials);
    }
  }
}
