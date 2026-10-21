import {
    Chart,
    CategoryScale,
    Filler,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Tooltip,
} from 'chart.js';

Chart.register(CategoryScale, Filler, LinearScale, LineController, LineElement, PointElement, Tooltip);

const SERIES = {
    players: { label: 'Players', color: '#22d3ee' },
    servers: { label: 'Online servers', color: '#a78bfa' },
    cpu: { label: 'CPU %', color: '#4ade80' },
    memory: { label: 'Memory (GB)', color: '#fbbf24' },
};

/**
 * Line chart for one metric of the dashboard stats. Updated through the
 * "stats-updated" Livewire event.
 */
export default (series, initial) => ({
    chart: null,

    init() {
        const { label, color } = SERIES[series];
        // Kept outside Alpine's reactivity, Chart.js mutates it heavily
        const chart = new Chart(this.$refs.canvas, {
            type: 'line',
            data: {
                labels: initial.labels,
                datasets: [{
                    label,
                    data: initial[series],
                    borderColor: color,
                    backgroundColor: `${color}22`,
                    fill: true,
                    tension: 0.3,
                    pointRadius: 0,
                    borderWidth: 2,
                }],
            },
            options: {
                maintainAspectRatio: false,
                animation: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: '#64748b', maxTicksLimit: 6 }, grid: { display: false } },
                    y: { beginAtZero: true, ticks: { color: '#64748b', precision: 0 }, grid: { color: '#1e293b' } },
                },
            },
        });
        this.chart = () => chart;
    },

    update(data) {
        const chart = this.chart();
        chart.data.labels = data.labels;
        chart.data.datasets[0].data = data[series];
        chart.update();
    },

    destroy() {
        this.chart?.().destroy();
    },
});
