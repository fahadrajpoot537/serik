<?php

namespace Botble\Page\Http\Controllers;

use Botble\Base\Facades\Assets;
use Botble\Base\Http\Actions\DeleteResourceAction;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Supports\Breadcrumb;
use Botble\Page\Forms\PageForm;
use Botble\Page\Http\Requests\PageRequest;
use Botble\Page\Models\Page;
use Botble\Page\Services\PageService;
use Botble\Page\Services\ShortcodeParserService;
use Botble\Page\Tables\PageTable;
use Botble\Shortcode\Facades\Shortcode;
use Botble\Theme\Facades\Theme;
use Illuminate\Http\Request;

class PageController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add(trans('packages/page::pages.menu_name'), route('pages.index'));
    }

    public function index(PageTable $pageTable)
    {
        $this->pageTitle(trans('packages/page::pages.menu_name'));

        return $pageTable->renderTable();
    }

    public function create()
    {
        $this->pageTitle(trans('packages/page::pages.create'));

        return PageForm::create()->renderForm();
    }

    public function store(PageRequest $request)
    {
        $form = PageForm::create()
            ->setRequest($request)
            ->save();

        return $this
            ->httpResponse()
            ->setPreviousRoute('pages.index')
            ->setNextRoute('pages.edit', $form->getModel()->getKey())
            ->withCreatedSuccessMessage();
    }

    public function edit(Page $page)
    {
        $this->pageTitle(trans('core/base::forms.edit_item', ['name' => $page->name]));

        return PageForm::createFromModel($page)->renderForm();
    }

    public function update(Page $page, PageRequest $request)
    {
        PageForm::createFromModel($page)
            ->setRequest($request)
            ->save();

        return $this
            ->httpResponse()
            ->setPreviousRoute('pages.index')
            ->withUpdatedSuccessMessage();
    }

    public function destroy(Page $page): DeleteResourceAction
    {
        return DeleteResourceAction::make($page);
    }

    public function visualBuilder(Page $page, ShortcodeParserService $parser)
    {
        abort_unless(setting('enable_page_visual_builder', true), 404);

        $this->pageTitle(trans('packages/page::pages.visual_builder', ['name' => $page->name]));

        $shortcodes = $parser->parse($page->content ?? '');

        $availableShortcodes = $this->getAvailableShortcodes();

        // Enqueue assets
        Assets::addStyles(['sortable'])
            ->addScripts(['sortable'])
            ->addStylesDirectly('vendor/core/packages/page/css/visual-builder.css')
            ->addScriptsDirectly('vendor/core/packages/page/js/visual-builder.js');

        return view('packages/page::visual-builder.index', compact('page', 'shortcodes', 'availableShortcodes'));
    }

    public function preview(Page $page, PageService $pageService, ShortcodeParserService $parser, Request $request)
    {
        abort_unless(setting('enable_page_visual_builder', true), 404);

        request()->merge(['preview' => 1, 'visual_builder' => 1]);

        if ($request->has('shortcodes')) {
            $shortcodesJson = $request->input('shortcodes');

            if (is_string($shortcodesJson)) {
                $shortcodes = json_decode($shortcodesJson, true);
            } else {
                $shortcodes = $shortcodesJson;
            }

            if (is_array($shortcodes) && count($shortcodes) > 0) {
                $content = $parser->serialize($shortcodes);
                $page->content = $content;
            }
        }

        // Get page data using the page service
        $data = $pageService->handleFrontRoutes(null);

        // Override the page in data
        $data['data']['page'] = $page;

        // Render the page using the theme
        return Theme::scope($data['view'], $data['data'], $data['default_view'])->render();
    }

    public function saveVisualBuilder(Page $page, Request $request, ShortcodeParserService $parser)
    {
        abort_unless(setting('enable_page_visual_builder', true), 404);

        $request->validate([
            'content' => ['required', 'string'],
        ]);

        // Get the serialized content from request
        $content = $request->input('content');

        // Update page content
        $page->content = $content;
        $page->save();

        return $this
            ->httpResponse()
            ->setMessage(trans('packages/page::pages.visual_builder_saved'));
    }

    protected function getAvailableShortcodes(): array
    {
        $shortcodes = [];

        if (! function_exists('shortcode')) {
            return $shortcodes;
        }

        foreach (Shortcode::getAll() as $key => $shortcode) {
            $shortcodes[] = [
                'key' => $key,
                'name' => $shortcode['name'] ?? $key,
                'description' => $shortcode['description'] ?? '',
                'previewImage' => $shortcode['preview_image'] ?? '',
                'url' => route('short-codes.ajax-get-admin-config', $key),
            ];
        }

        return $shortcodes;
    }

    public function renderShortcodeItems(Request $request)
    {
        abort_unless(setting('enable_page_visual_builder', true), 404);

        $shortcodes = $request->input('shortcodes', []);
        $activeId = $request->input('activeId');

        $html = '';
        foreach ($shortcodes as $shortcode) {
            $html .= view('packages/page::visual-builder.partials.shortcode-item', [
                'shortcode' => $shortcode,
                'activeId' => $activeId,
            ])->render();
        }

        return $this
            ->httpResponse()
            ->setData(['html' => $html]);
    }

    public function renderShortcodeTypes(Request $request)
    {
        abort_unless(setting('enable_page_visual_builder', true), 404);

        $availableShortcodes = $this->getAvailableShortcodes();

        $html = '';
        foreach ($availableShortcodes as $shortcode) {
            $html .= view('packages/page::visual-builder.partials.shortcode-type-card', [
                'shortcode' => $shortcode,
            ])->render();
        }

        return $this
            ->httpResponse()
            ->setData(['html' => $html]);
    }
}
