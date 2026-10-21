import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import serverConsole from './console';
import statsChart from './charts';

Alpine.data('toasts', (initial) => ({
    items: [],
    nextId: 1,
    init() {
        if (initial) {
            this.add({ message: initial, type: 'success' });
        }
    },
    add({ message, type = 'success' }) {
        const id = this.nextId++;
        this.items.push({ id, message, type });
        setTimeout(() => this.remove(id), type === 'error' ? 8000 : 4000);
    },
    remove(id) {
        this.items = this.items.filter((item) => item.id !== id);
    },
}));

Alpine.data('serverConsole', serverConsole);
Alpine.data('statsChart', statsChart);

Livewire.start();
