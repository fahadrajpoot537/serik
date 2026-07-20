



@php
    $itemsPerRow ??= 3;
@endphp

@if ($properties->isNotEmpty())
    <div class="row row-cols-1 row-cols-sm-2 @if ($itemsPerRow > 2) row-cols-md-{{ $itemsPerRow - 1 }} @endif row-cols-xl-{{ $itemsPerRow }}">
        @foreach($properties as $property)
            <div class="col">
                @include(Theme::getThemeNamespace('views.real-estate.properties.item-grid'))
            </div>
        @endforeach
    </div>
@endif


<script>
    
  document.addEventListener('DOMContentLoaded', () => {

    // Select ALL elements using class instead of id
    const currencyInputs = document.querySelectorAll('.price_main');

    function formatCurrency(value) {
        let number = value.replace(/[^0-9.]/g, '');
        number = parseFloat(number);

        if (!isNaN(number)) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(number);
        }
        return '';
    }

    currencyInputs.forEach((currencyInput) => {
        currencyInput.addEventListener('input', (e) => {
            e.target.value = formatCurrency(e.target.value);
        });
    });

});

</script>