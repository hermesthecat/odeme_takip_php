/**
 * @author A. Kerem Gök
 * Grafik işlemleri
 */

// Grafik renk paleti
const chartColors = {
    primary: '#4CAF50',
    secondary: '#2196F3',
    danger: '#F44336',
    warning: '#FFC107',
    info: '#9C27B0',
    dark: '#607D8B',
    success: '#8BC34A',
    orange: '#FF5722'
};

// Grafik arka plan renk paleti (alpha: 0.1)
const chartBackgroundColors = {
    primary: 'rgba(76, 175, 80, 0.1)',
    secondary: 'rgba(33, 150, 243, 0.1)',
    danger: 'rgba(244, 67, 54, 0.1)',
    warning: 'rgba(255, 193, 7, 0.1)',
    info: 'rgba(156, 39, 176, 0.1)',
    dark: 'rgba(96, 125, 139, 0.1)',
    success: 'rgba(139, 195, 74, 0.1)',
    orange: 'rgba(255, 87, 34, 0.1)'
};

// Çizgi grafik oluştur
function createLineChart(ctx, data, options = {}) {
    return new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: data.datasets.map(dataset => ({
                label: dataset.label,
                data: dataset.data,
                borderColor: chartColors[dataset.color || 'primary'],
                backgroundColor: chartBackgroundColors[dataset.color || 'primary'],
                tension: 0.4,
                ...dataset
            }))
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: options.legendPosition || 'top',
                },
                title: {
                    display: !!options.title,
                    text: options.title || ''
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: options.yAxisCallback || (value => value)
                    }
                }
            },
            ...options
        }
    });
}

// Pasta grafik oluştur
function createPieChart(ctx, data, options = {}) {
    return new Chart(ctx, {
        type: options.type || 'pie',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.values,
                backgroundColor: data.colors || Object.values(chartColors),
                ...data
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: options.legendPosition || 'right',
                },
                title: {
                    display: !!options.title,
                    text: options.title || ''
                }
            },
            ...options
        }
    });
}

// Çubuk grafik oluştur
function createBarChart(ctx, data, options = {}) {
    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: data.datasets.map(dataset => ({
                label: dataset.label,
                data: dataset.data,
                backgroundColor: dataset.colors || chartColors[dataset.color || 'primary'],
                ...dataset
            }))
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: options.legendPosition || 'top',
                },
                title: {
                    display: !!options.title,
                    text: options.title || ''
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: options.yAxisCallback || (value => value)
                    }
                }
            },
            ...options
        }
    });
}

// Karma grafik oluştur (çizgi + çubuk)
function createMixedChart(ctx, data, options = {}) {
    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: data.datasets.map(dataset => ({
                type: dataset.type || 'bar',
                label: dataset.label,
                data: dataset.data,
                backgroundColor: dataset.type === 'line' ? 
                    chartBackgroundColors[dataset.color || 'primary'] : 
                    chartColors[dataset.color || 'primary'],
                borderColor: dataset.type === 'line' ? 
                    chartColors[dataset.color || 'primary'] : undefined,
                tension: dataset.type === 'line' ? 0.4 : undefined,
                ...dataset
            }))
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: options.legendPosition || 'top',
                },
                title: {
                    display: !!options.title,
                    text: options.title || ''
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: options.yAxisCallback || (value => value)
                    }
                }
            },
            ...options
        }
    });
}

// Grafik güncelleme fonksiyonu
function updateChart(chart, newData, newOptions = {}) {
    // Veri güncelleme
    if (newData.labels) {
        chart.data.labels = newData.labels;
    }
    
    if (newData.datasets) {
        chart.data.datasets = newData.datasets.map(dataset => ({
            ...dataset,
            backgroundColor: dataset.type === 'line' ? 
                chartBackgroundColors[dataset.color || 'primary'] : 
                chartColors[dataset.color || 'primary'],
            borderColor: dataset.type === 'line' ? 
                chartColors[dataset.color || 'primary'] : undefined
        }));
    }

    // Seçenekleri güncelle
    if (Object.keys(newOptions).length > 0) {
        chart.options = {
            ...chart.options,
            ...newOptions
        };
    }

    // Grafiği yenile
    chart.update();
}

// Grafik animasyon seçenekleri
const chartAnimations = {
    fadeIn: {
        animation: {
            duration: 1000,
            easing: 'easeInOutQuart'
        }
    },
    slideIn: {
        animation: {
            x: {
                duration: 1000,
                from: 500
            }
        }
    },
    bounceIn: {
        animation: {
            duration: 1000,
            easing: 'easeInElastic'
        }
    }
};

// Para birimi formatı için yardımcı fonksiyon
function formatChartMoney(value, currency = 'TRY') {
    return new Intl.NumberFormat('tr-TR', {
        style: 'currency',
        currency: currency
    }).format(value);
}

// Tarih formatı için yardımcı fonksiyon
function formatChartDate(date, format = 'short') {
    const options = {
        short: { day: 'numeric', month: 'short' },
        medium: { day: 'numeric', month: 'short', year: 'numeric' },
        long: { day: 'numeric', month: 'long', year: 'numeric' }
    };
    
    return new Date(date).toLocaleDateString('tr-TR', options[format]);
}

// Yüzde formatı için yardımcı fonksiyon
function formatChartPercentage(value) {
    return `%${value.toFixed(1)}`;
}

// Grafik tooltip özelleştirme
function createCustomTooltip(tooltipModel) {
    return {
        callbacks: {
            label: function(context) {
                let label = context.dataset.label || '';
                if (label) {
                    label += ': ';
                }
                if (tooltipModel.format === 'money') {
                    label += formatChartMoney(context.parsed.y, tooltipModel.currency);
                } else if (tooltipModel.format === 'percentage') {
                    label += formatChartPercentage(context.parsed.y);
                } else {
                    label += context.parsed.y;
                }
                return label;
            }
        }
    };
}

// Grafik lejant özelleştirme
function createCustomLegend(chart, container) {
    const legendItems = chart.data.datasets.map((dataset, index) => ({
        text: dataset.label,
        fillStyle: dataset.backgroundColor,
        hidden: !chart.isDatasetVisible(index),
        index: index
    }));

    container.innerHTML = legendItems.map(item => `
        <div class="legend-item" data-index="${item.index}">
            <span class="legend-color" style="background-color: ${item.fillStyle}"></span>
            <span class="legend-text">${item.text}</span>
        </div>
    `).join('');

    container.querySelectorAll('.legend-item').forEach(item => {
        item.addEventListener('click', () => {
            const index = parseInt(item.dataset.index);
            const meta = chart.getDatasetMeta(index);
            meta.hidden = !meta.hidden;
            item.classList.toggle('hidden');
            chart.update();
        });
    });
} 