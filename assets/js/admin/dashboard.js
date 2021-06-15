import {Chart, registerables} from "chart.js";

;(function (global) {
    'use strict'
    let GOVUK = global.GOVUK || {};

    GOVUK.rfsDashboard = {
        _init: function() {
            Chart.register(...registerables);

            let charts = document.querySelectorAll(".pie-chart");
            charts.forEach((elem) => {
                let chartData = JSON.parse(elem.dataset.chart);
                let values = Object.values(chartData);

                let config = {
                    type: 'bar',
                    data: {
                        datasets: [{
                            data: values.map(x => x.count),
                            backgroundColor: values.map(x => x.background),
                            hoverOffset: 4,
                        }],
                        labels: Object.keys(chartData)
                    },
                    options: {
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                };

                new Chart(elem, config);
            })
        },
    }

    GOVUK.rfsDashboard.init = GOVUK.rfsDashboard._init.bind(GOVUK.rfsDashboard);

    global.GOVUK = GOVUK;
})(window); // eslint-disable-line semi