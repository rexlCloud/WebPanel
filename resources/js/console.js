import { ansiToHtml } from './ansi';

const MAX_LINES = 2000;
const POLL_INTERVAL = 3000;
// Close codes sent by the RestModule
const CLOSE_UNAUTHORIZED = 4401;
const CLOSE_NOT_FOUND = 4404;

/**
 * Live server console. Streams through the RestModule WebSocket and falls back to
 * polling through Livewire when the browser cannot reach the API directly.
 */
export default ({ canExecute }) => ({
    status: 'connecting', // connecting | live | polling | ended
    canExecute,
    command: '',
    commandHistory: [],
    historyIndex: -1,
    socket: null,
    pollTimer: null,
    reconnectDelay: 1000,
    destroyed: false,

    init() {
        this.connect();
    },

    destroy() {
        this.destroyed = true;
        this.socket?.close();
        clearInterval(this.pollTimer);
    },

    async connect() {
        if (this.destroyed) return;
        this.status = 'connecting';
        let url = null;
        try {
            url = await this.$wire.consoleSocketUrl();
        } catch {
            // handled as "no URL" below
        }
        // Browsers refuse ws:// from an https:// page (the cloud URL is http://), so poll right away
        if (!url || (location.protocol === 'https:' && url.startsWith('ws:'))) {
            this.startPolling();
            return;
        }

        let opened = false;
        let socket;
        try {
            socket = new WebSocket(url);
        } catch {
            // e.g. a SecurityError the browser throws instead of connecting
            this.startPolling();
            return;
        }
        this.socket = socket;
        socket.onopen = () => {
            opened = true;
            this.status = 'live';
            this.reconnectDelay = 1000;
            this.stopPolling();
        };
        socket.onmessage = (event) => this.handle(JSON.parse(event.data));
        socket.onclose = (event) => {
            if (this.destroyed || this.socket !== socket) return;
            if (event.code === CLOSE_NOT_FOUND) {
                this.status = 'ended';
                this.appendSystem('The server is no longer running.');
                return;
            }
            if (!opened && event.code !== CLOSE_UNAUTHORIZED) {
                // The browser cannot reach the API (network, mixed content, ...)
                this.startPolling();
                return;
            }
            this.status = 'connecting';
            setTimeout(() => this.connect(), this.reconnectDelay);
            this.reconnectDelay = Math.min(this.reconnectDelay * 2, 30000);
        };
    },

    startPolling() {
        this.status = 'polling';
        this.poll();
        clearInterval(this.pollTimer);
        this.pollTimer = setInterval(() => this.poll(), POLL_INTERVAL);
    },

    stopPolling() {
        clearInterval(this.pollTimer);
        this.pollTimer = null;
    },

    async poll() {
        try {
            this.replace(await this.$wire.consoleLines());
        } catch {
            // keep the last output; the next poll retries
        }
    },

    handle(message) {
        if (message.type === 'history') {
            this.canExecute = message.can_execute;
            this.replace(message.lines);
        } else if (message.type === 'line' && message.line !== null) {
            this.append([message.line]);
        } else if (message.type === 'error') {
            this.appendSystem(message.message);
        }
    },

    replace(lines) {
        this.$refs.output.replaceChildren();
        this.append(lines.filter((line) => line !== null));
        this.scrollToBottom(true);
    },

    append(lines) {
        const output = this.$refs.output;
        const stick = this.isAtBottom();
        const fragment = document.createDocumentFragment();
        for (const line of lines) {
            const row = document.createElement('div');
            row.innerHTML = ansiToHtml(line) || '&nbsp;';
            fragment.appendChild(row);
        }
        output.appendChild(fragment);
        while (output.childElementCount > MAX_LINES) {
            output.firstElementChild.remove();
        }
        if (stick) this.scrollToBottom(true);
    },

    appendSystem(text) {
        const row = document.createElement('div');
        row.className = 'text-amber-300';
        row.textContent = `» ${text}`;
        this.$refs.output.appendChild(row);
        this.scrollToBottom(true);
    },

    isAtBottom() {
        const el = this.$refs.scroller;
        return el.scrollHeight - el.scrollTop - el.clientHeight < 40;
    },

    scrollToBottom(force = false) {
        if (force || this.isAtBottom()) {
            this.$nextTick(() => {
                this.$refs.scroller.scrollTop = this.$refs.scroller.scrollHeight;
            });
        }
    },

    async send() {
        const command = this.command.trim();
        if (!command) return;
        this.commandHistory.unshift(command);
        this.historyIndex = -1;
        this.command = '';
        if (this.socket?.readyState === WebSocket.OPEN) {
            this.socket.send(JSON.stringify({ type: 'command', command }));
        } else if (await this.$wire.sendCommand(command)) {
            this.poll();
        }
    },

    previousCommand() {
        if (this.historyIndex < this.commandHistory.length - 1) {
            this.command = this.commandHistory[++this.historyIndex];
        }
    },

    nextCommand() {
        this.historyIndex = Math.max(-1, this.historyIndex - 1);
        this.command = this.historyIndex === -1 ? '' : this.commandHistory[this.historyIndex];
    },
});
