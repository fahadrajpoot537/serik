<header
    class="navbar navbar-expand-md d-print-none vb-header"
    id="vb-header"
>
    <div class="container-fluid">
        <div class="navbar-brand d-flex align-items-center gap-2">
            <x-core::icon name="ti ti-layout-dashboard" />
            <span class="fw-bold">{{ $page->name }}</span>
            <span
                class="badge bg-warning-lt d-none"
                id="vb-unsaved-indicator"
            >
                <x-core::icon
                    name="ti ti-circle-dot"
                    class="icon-sm"
                />
                {{ trans('packages/page::pages.visual_builder_unsaved_changes') }}
            </span>
        </div>

        <div class="navbar-nav flex-row order-md-last">
            <div class="nav-item me-2 d-none d-md-flex">
                <div
                    class="btn-group"
                    role="group"
                    id="vb-device-modes"
                >
                    <input
                        type="radio"
                        class="btn-check"
                        name="device-mode"
                        id="device-desktop"
                        autocomplete="off"
                        checked
                        data-device="desktop"
                    >
                    <label
                        class="btn btn-icon"
                        for="device-desktop"
                        data-bs-toggle="tooltip"
                        title="{{ trans('packages/page::pages.visual_builder_device_desktop') }}"
                    >
                        <x-core::icon name="ti ti-device-desktop" />
                    </label>

                    <input
                        type="radio"
                        class="btn-check"
                        name="device-mode"
                        id="device-tablet"
                        autocomplete="off"
                        data-device="tablet"
                    >
                    <label
                        class="btn btn-icon"
                        for="device-tablet"
                        data-bs-toggle="tooltip"
                        title="{{ trans('packages/page::pages.visual_builder_device_tablet') }}"
                    >
                        <x-core::icon name="ti ti-device-tablet" />
                    </label>

                    <input
                        type="radio"
                        class="btn-check"
                        name="device-mode"
                        id="device-mobile"
                        autocomplete="off"
                        data-device="mobile"
                    >
                    <label
                        class="btn btn-icon"
                        for="device-mobile"
                        data-bs-toggle="tooltip"
                        title="{{ trans('packages/page::pages.visual_builder_device_mobile') }}"
                    >
                        <x-core::icon name="ti ti-device-mobile" />
                    </label>
                </div>
            </div>

            <div class="nav-item">
                <div class="btn-list">
                    <x-core::button
                        type="button"
                        id="vb-save-btn"
                        color="success"
                        icon="ti ti-device-floppy"
                    >
                        <span class="d-none d-sm-inline">{{ trans('packages/page::pages.save') }}</span>
                    </x-core::button>

                    <x-core::button
                        tag="a"
                        :href="route('pages.edit', $page)"
                        id="vb-close-btn"
                        color="secondary"
                        icon="ti ti-x"
                    >
                        <span class="d-none d-sm-inline">{{ trans('packages/page::pages.close') }}</span>
                    </x-core::button>
                </div>
            </div>
        </div>
    </div>
</header>
