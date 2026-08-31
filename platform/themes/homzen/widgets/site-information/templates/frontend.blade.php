<div class="col-lg-4 col-md-6">
    <div class="footer-cl-1">
        <p class="text-variant-2">{!! BaseHelper::clean($config['about']) !!}</p>
        @if($items->isNotEmpty())
            <ul class="mt-12">
                @foreach($items as $item)
                    @php
                        $icon = (string) ($item['icon'] ?? '');
                        $rawText = (string) ($item['text'] ?? '');
                        $isAddress = \App\Support\OfficeAddress::isAddressItem($icon, $rawText);
                        $mapsUrl = $isAddress ? \App\Support\OfficeAddress::mapsUrl() : '';
                    @endphp
                    <li class="mt-12 d-flex align-items-center gap-8">
                        <x-core::icon :name="$icon" class="text-variant-2" style="width: 1.25rem; height: 1.25rem; flex-shrink: 0;" />
                        @if($isAddress && $mapsUrl !== '')
                            <a href="{{ $mapsUrl }}"
                               class="serik-footer-address-link text-white"
                               target="_blank"
                               rel="noopener noreferrer"
                               aria-label="{{ \App\Support\OfficeAddress::MAPS_LABEL }}">
                                <span class="serik-footer-address-text">{!! BaseHelper::clean(nl2br($rawText)) !!}</span>
                            </a>
                        @else
                            <p class="text-white">{!! BaseHelper::clean(nl2br($rawText)) !!}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
