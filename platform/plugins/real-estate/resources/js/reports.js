'use strict';

class ReportsComponent {
    constructor() {
        this.init();
    }

    init() {
        this.initCharts();
    }

    initCharts() {
        // Property by type chart
        if ($('#property-by-type-chart').length) {
            new Morris.Donut({
                element: 'property-by-type-chart',
                data: window.propertyByTypeStats || [],
                colors: ['#3c8dbc', '#f56954', '#00a65a', '#f39c12', '#00c0ef', '#d2d6de'],
                resize: true,
                responsive: true,
            });
        }

        // Monthly property chart
        if ($('#monthly-property-chart').length) {
            new Morris.Bar({
                element: 'monthly-property-chart',
                data: window.monthlyPropertyStats || [],
                xkey: 'month',
                ykeys: ['count'],
                labels: [window.trans.property_count],
                barColors: ['#00a65a'],
                hideHover: 'auto',
                resize: true,
                responsive: true,
                postUnits: '',
                gridTextSize: 10,
            });
        }

        // Handle window resize to redraw charts
        $(window).on('resize', function() {
            if (this.resizeTO) clearTimeout(this.resizeTO);
            this.resizeTO = setTimeout(function() {
                $(window).trigger('resizeEnd');
            }, 500);
        });

        $(window).on('resizeEnd', function() {
            if ($('#property-by-type-chart').length) {
                $('#property-by-type-chart').empty();
                new Morris.Donut({
                    element: 'property-by-type-chart',
                    data: window.propertyByTypeStats || [],
                    colors: ['#3c8dbc', '#f56954', '#00a65a', '#f39c12', '#00c0ef', '#d2d6de'],
                    resize: true,
                    responsive: true,
                });
            }

            if ($('#monthly-property-chart').length) {
                $('#monthly-property-chart').empty();
                new Morris.Bar({
                    element: 'monthly-property-chart',
                    data: window.monthlyPropertyStats || [],
                    xkey: 'month',
                    ykeys: ['count'],
                    labels: [window.trans.property_count],
                    barColors: ['#00a65a'],
                    hideHover: 'auto',
                    resize: true,
                    responsive: true,
                    postUnits: '',
                    gridTextSize: 10,
                });
            }
        });
    }
}

$(document).ready(function () {
    new ReportsComponent();
});
