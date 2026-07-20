class Currencies {
    constructor() {
        this.template = $('#currency_template').html()
        this.totalItem = 0

        this.deletedItems = []

        this.initData()
        this.handleForm()
        this.handleAdvancedToggle()
    }

    initData() {
        let _self = this
        let data = $.parseJSON($('#currencies').html())

        $.each(data, (index, item) => {
            let template = _self.template
                .replace(/__id__/gi, item.id)
                .replace(/__position__/gi, item.order)
                .replace(/__isPrefixSymbolChecked__/gi, item.is_prefix_symbol == 1 ? 'selected' : '')
                .replace(/__notIsPrefixSymbolChecked__/gi, item.is_prefix_symbol == 0 ? 'selected' : '')
                .replace(/__isDefaultChecked__/gi, item.is_default == 1 ? 'checked' : '')
                .replace(/__westernFormatChecked__/gi, (item.number_format_style || 'western') == 'western' ? 'selected' : '')
                .replace(/__indianFormatChecked__/gi, item.number_format_style == 'indian' ? 'selected' : '')
                .replace(/__spaceBetweenPriceAndCurrencyChecked__/gi, item.space_between_price_and_currency == 1 ? 'checked' : '')
                .replace(/__title__/gi, item.title)
                .replace(/__decimals__/gi, item.decimals)
                .replace(/__exchangeRate__/gi, item.exchange_rate)
                .replace(/__symbol__/gi, item.symbol)

            $('.swatches-container .swatches-list').append(template)

            _self.totalItem++
        })
    }

    addNewAttribute() {
        let _self = this

        let template = _self.template
            .replace(/__id__/gi, 0)
            .replace(/__position__/gi, _self.totalItem)
            .replace(/__isPrefixSymbolChecked__/gi, '')
            .replace(/__notIsPrefixSymbolChecked__/gi, '')
            .replace(/__isDefaultChecked__/gi, _self.totalItem == 0 ? 'checked' : '')
            .replace(/__westernFormatChecked__/gi, 'selected')
            .replace(/__indianFormatChecked__/gi, '')
            .replace(/__spaceBetweenPriceAndCurrencyChecked__/gi, '')
            .replace(/__title__/gi, '')
            .replace(/__decimals__/gi, 0)
            .replace(/__exchangeRate__/gi, 1)
            .replace(/__symbol__/gi, '')
        $('.swatches-container .swatches-list').append(template)

        _self.totalItem++
    }

    exportData() {
        let data = []

        $('.swatches-container .swatches-list li.currency-item').each((index, item) => {
            let $current = $(item)
            data.push({
                id: $current.data('id'),
                is_default: $current.find('.currency-row [data-type=is_default] input[type=radio]').is(':checked') ? 1 : 0,
                order: $current.index(),
                title: $current.find('.currency-row [data-type=title] input').val(),
                symbol: $current.find('.currency-row [data-type=symbol] input').val(),
                decimals: $current.find('.currency-advanced-settings [data-type=decimals]').val(),
                number_format_style: $current.find('.currency-advanced-settings [data-type=number_format_style]').val(),
                space_between_price_and_currency: $current.find('.currency-advanced-settings [data-type=space_between_price_and_currency]').is(':checked') ? 1 : 0,
                exchange_rate: $current.find('.currency-row [data-type=exchange_rate] input').val(),
                is_prefix_symbol: $current.find('.currency-advanced-settings [data-type=is_prefix_symbol]').val(),
            })
        })

        return data
    }

    handleForm() {
        let _self = this

        $('.swatches-container .swatches-list').sortable()

        $('body')
            .on('submit', '.main-setting-form', () => {
                let data = _self.exportData()

                $('#currencies').val(JSON.stringify(data))

                $('#deleted_currencies').val(JSON.stringify(_self.deletedItems))
            })
            .on('click', '.js-add-new-attribute', (event) => {
                event.preventDefault()

                _self.addNewAttribute()
            })
            .on('click', '.swatches-container .swatches-list li .remove-item a', (event) => {
                event.preventDefault()

                let $item = $(event.currentTarget).closest('li')

                _self.deletedItems.push($item.data('id'))

                $item.remove()
            })
    }

    handleAdvancedToggle() {
        $(document).on('click', '.toggle-advanced', (event) => {
            event.preventDefault()

            const $button = $(event.currentTarget)
            const $currencyItem = $button.closest('.currency-item')
            const $advancedSettings = $currencyItem.find('.currency-advanced-settings')

            $advancedSettings.slideToggle(300, function() {
                if ($advancedSettings.is(':visible')) {
                    $button.addClass('active')
                } else {
                    $button.removeClass('active')
                }
            })
        })
    }
}

$(window).on('load', () => {
    new Currencies()
})
